<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
    * API NAME: Hydako API
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @integrator @mccoy.php.ph
**/

abstract class Abstract_game_api_common_hydako extends Abstract_game_api {

    protected $token = null;

	const MD5_FIELDS_FOR_ORIGINAL = [
        'seq_id',
        'user_id',
        'gameName',
        'slotType',
        'status',
        'bet',
        'win',
        'jackpot',
        'regDate',
        'freeWin',
        'startCash',
        'endCash',
        'coin',
        'orig_regDate'
    ];

	const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet',
        'win',
        'jackpot',
        'freeWin',
        'startCash',
        'endCash',
    ];

	const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'player_username',
        'start_at',
        'end_at',
        'bet_at',
    ];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    const IS_AUTH='IS_AUTH';

    const SUCCESS=0;
    const EXPIRED_TOKEN = -30101;

    const URI_MAP = [
        self::API_createPlayer => '/api/LoginPlayer',
        self::API_login => '/api/LoginPlayer',
        self::API_depositToGame => '/api/Deposit',
        self::API_withdrawFromGame => '/api/Withdraw',
        self::API_queryPlayerBalance => '/api/GetBalance',
        self::API_syncGameRecords => '/api/GetAgentGameHistory',
        self::API_queryTransaction => '/api/GetTransactionInfoByPlatformTid'
    ];

    private $cachePrefix = 'HYDAKO-TOKEN-';
    private $expired_token = false;
    private $player_name = '';

    public function __construct() {
        parent::__construct();
        $this->CI->load->model('game_provider_auth');
        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('key');
        $this->game_url = $this->getSystemInfo('game_url');
        $this->api = null;

        $this->page_size = $this->getSystemInfo('page_size', 3000);
        $this->terminate_on_error_count = $this->getSystemInfo('terminate_on_error_count', 3);

    }

    public function generateUrl($apiName, $params) {
        return $this->api_url . self::URI_MAP[$apiName];
    }

    public function getHttpHeaders($params) {

        if($this->api == self::IS_AUTH) {
            $header = array(
                "Content-Type" => "application/json",
                "Authorization" => $this->token,
                "User-Agent" => "TripleOne"
            );
        } else {
            $header = array(
                "Content-Type" => "application/json",
                "User-Agent" => "TripleOne"
            );
        }

        $this->utils->debug_log('Hydako (Request Header)', $header);

        return $header;

    }

    protected function customHttpCall($ch, $params) {

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $this->utils->debug_log('Hydako (Request Field)', $params);

    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function callApi($apiName, $params, $context = null) {

        $response = parent::callApi($apiName, $params, $context);

        if($response['success'] == false && $this->expired_token) {
            $this->login($this->player_name);
            $response = parent::callApi($apiName, $params, $context);
        }

        return $response;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode){

        $success = false;
        if(($statusCode == 200 || $statusCode == 201) && $resultArr['result']['code'] == self::SUCCESS){
            $success=true;
        }

        if($resultArr['result']['code'] == self::EXPIRED_TOKEN) {
            $this->expired_token = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Hydako Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;

    }

    public function login($playerName, $password = null, $extra = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);
        $password = $this->getPasswordByGameUsername($gameUsername);
        $this->player_name = $playerName;
        if(!$this->expired_token) {
            $cache_key = $this->cachePrefix . $playerId;
            $token = $this->CI->utils->getJsonFromCache($cache_key);
            $password = $this->getPasswordByGameUsername($gameUsername);
            if(isset($token) && !empty($token)) {
                if($this->isTokenValid($token)) {
                    $this->token = $token['token'];
                    return [
                        'success' => true,
                        'token' => $token['token']
                    ];
                }
            }
        }

        $this->deviceType = $this->CI->utils->is_mobile() ? 1 : 2;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
        );

        $params = array(
            'userId' => $gameUsername,
            'password' => $password,
            'countryCode' => $this->country_code,
            'apiKey' => $this->api_key,
            'currency' => $this->currency,
            'deviceType' => $this->deviceType
        );

        $this->CI->utils->debug_log('Hydako (login)', $params);

        return $this->callApi(self::API_login, $params, $context);

    }

    public function processResultForLogin($params){

        $gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
        $playerId = @$this->getVariableFromContext($params, 'playerId');
        $playerName = @$this->getVariableFromContext($params, 'playerName');
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $this->CI->utils->debug_log('Hydako (processResultForLogin)', $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            $this->token = $resultArr['token'];
            $result['token'] = $resultArr['token'];
            $token_timeout = new DateTime($this->utils->getNowForMysql());
            $minutes = ((int)$resultArr['expireTime']/60)-1;
            $token_timeout->modify("+".$minutes." minutes");
            $expireTime['timeout']=$token_timeout->format('Y-m-d H:i:s');
            $resultToken=[
                'token'=>$resultArr['token'],
                'refreshToken'=>$resultArr['refreshToken'],
                'expireTime'=>$expireTime['timeout']
            ];

            $cache_key = $this->cachePrefix . $playerId;
            $this->CI->utils->saveJsonToCache($cache_key, $resultToken);
            $this->expired_token = false;
        } else {
            $result['token']=null;
        }

        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $this->deviceType = $this->CI->utils->is_mobile() ? 1 : 2;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'userId' => $gameUsername,
            'password' => $password,
            'countryCode' => $this->country_code,
            'apiKey' => $this->api_key,
            'currency' => $this->currency,
            'deviceType' => $this->deviceType
        );

        $this->CI->utils->debug_log('Hydako (createPlayer) :', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        // print_r($resultArr);exit;
        $this->CI->utils->debug_log('Hydako (processResultForCreatePlayer)', $resultArr);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $token_timeout = new DateTime($this->utils->getNowForMysql());
            $minutes = ((int)$resultArr['expireTime']/60)-1;
            $token_timeout->modify("+".$minutes." minutes");
            $expireTime['timeout']=$token_timeout->format('Y-m-d H:i:s');
            $resultToken=[
                'token'=>$resultArr['token'],
                'refreshToken'=>$resultArr['refreshToken'],
                'expireTime'=>$expireTime['timeout']
            ];
            $this->CI->game_provider_auth->addGameAdditionalInfo($gameUsername, json_encode($resultToken), $this->getPlatformCode());
            $result['exists'] = true;
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

        $this->login($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

        $params = array(
            'amount' => $amount,
            'platformTId' => $external_transaction_id
        );

        $this->api = self::IS_AUTH;

        $this->CI->utils->debug_log('Hydako (depositToGame)', $params);

        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    public function processResultForDepositToGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('Hydako (processResultForDepositToGame)', $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

        $this->login($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

        $params = array(
            'amount' => $amount,
            'platformTId' => $external_transaction_id
        );

        $this->api = self::IS_AUTH;

        $this->CI->utils->debug_log('Hydako (withdrawFromGame)', $params);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);

    }

    public function processResultForWithdrawFromGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('Hydako (processResultForWithdrawFromGame)', $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

    }

    public function queryPlayerBalance($playerName) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $this->login($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        ];

        $params = [];

        $this->CI->utils->debug_log('Hydako (queryPlayerBalance)', $params);

        $this->api = self::IS_AUTH;

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('Hydako (processResultForQueryPlayerBalance)', $resultArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id'=>$responseResultId];

        if($success){
            if(isset($resultArr['realAmount'])){
                $result['balance'] = $resultArr['realAmount'];
            }else{
                //wrong result, call failed
                $success=false;
            }
        }

        return [$success, $result];
    }

    public function queryForwardGame($playerName, $extra) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->login($playerName);
        $this->CI->utils->debug_log(__FUNCTION__,'Hydako (token): ', $token);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $language = isset($this->language) ? $this->language : $this->getLauncherLanguage($extra['language']);

        $params = array(
            'token' => $token['token'],
            'gid' => $game_code,
            'lang' => $language
        );

        $url = $this->game_url . '?' . http_build_query($params);

        $this->CI->utils->debug_log('Hydako (queryForwardGame)', $params, 'URL', $url);

        return ['success' => true,'url' => $url];

    }

    public function getLauncherLanguage($currentLang) {

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh-chs';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en-us":
                $language = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-th":
                $language = 'th';
                break;
            default:
                $language = 'en';
                break;
        }

        return $language;

    }

    public function queryTransaction($transactionId, $extra) {
        $playerName = $extra['playerName'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'external_transaction_id' => $transactionId,
        ];

        $params = [
            'apiKey' => $this->api_key,
            'platformTId' => $transactionId
        ];

        $this->CI->utils->debug_log('Hydako (queryTransaction)', $params);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {

        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('Hydako (processResultForQueryTransaction)', $resultArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success){
            $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);

    }


    public function syncOriginalGameLogs($token) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = $startDateTime->format('Y-m-d');
        $endDate   = $endDateTime->format('Y-m-d');

        $success=false;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

        $errorCount=0;
        $rowsCount=0;
        $dataCount=0;
        //always start from 1
        $currentPage = 1;
        $done = false;
        while (!$done) {
            $params = [
                'apiKey' => $this->api_key,
                'pageNumber' => $currentPage,
                'pageSize' => $this->page_size,
                'startTime' => $startDate,
                'endTime' => $endDate,
            ];

            $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

            $this->CI->utils->debug_log('Hydako api_result: ', $api_result);
            $done = false;
            if ($api_result && $api_result['success']) {
                $totalCount = isset($api_result['gameHistoryCount']) ? (int)$api_result['gameHistoryCount'] : 0;
                $itemCount = isset($api_result['itemCount']) ? (int)$api_result['itemCount'] : 0;
                $dataCount += $totalCount;

                //if next data is less than page size, no next data is expected
                //or no data at all
                if($totalCount < $this->page_size || $totalCount<=0){
                    $done=true;
                }

                $this->CI->utils->debug_log('Hydako currentPage: ',$currentPage,'totalCount',$totalCount,'dataCount', $dataCount,'itemCount',$itemCount, 'done', $done, 'result', $api_result);
            }else{
                $errorCount++;
            }

            if ($done) {
                $success = true;
            }

            //force end if met number of error attempt
            if($this->terminate_on_error_count<>0 && $errorCount>=$this->terminate_on_error_count){
                $done=true;
                $this->CI->utils->debug_log('Hydako Error request count: '. $errorCount);
            }

            $currentPage += 1;
        }



        return array('success' => $success, 'rows_count'=>$rowsCount);

    }

    public function processResultForSyncOriginalGameLogs($params){
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'data_count' => 0,
            'gameHistoryCount' => count($resultArr['gameHistoryResponse']),
            'itemCount' => (isset($resultArr['itemCount'])?(int)$resultArr['itemCount']:0)
        ];
        $this->CI->utils->debug_log('Hydako resultArr: ', $result);
        $gameRecords = isset($resultArr['gameHistoryResponse']) ? $resultArr['gameHistoryResponse'] : null;

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $this->processGameRecords($gameRecords, $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_game_logs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);

        }


        return array($success, $result);
    }

    private function processGameRecords(&$gameRecords, $extra) {

        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['seq_id'] = isset($record['seq']) ? $record['seq'] : null;
                $user_id = isset($record['id']) ? explode("_", $record['id']) : null;
                $data['user_id'] = isset($record['id']) ? $user_id[1] : null;
                $data['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
                $data['slotType'] = isset($record['slotType']) ? $record['slotType'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['bet'] = isset($record['bet']) ? $record['bet'] : null;
                $data['win'] = isset($record['win']) ? $record['win'] : null;
                $data['jackpot'] = isset($record['jackpot']) ? $record['jackpot'] : null;
                $data['regDate'] = isset($record['regDate']) ? $this->gameTimeToServerTime($record['regDate']) : null;
                $data['ip'] = isset($record['ip']) ? $record['ip'] : null;
                $data['freeWin'] = isset($record['freeWin']) ? $record['freeWin'] : null;
                $data['startCash'] = isset($record['startCash']) ? $record['startCash'] : null;
                $data['endCash'] = isset($record['endCash']) ? $record['endCash'] : null;
                $data['coin'] = isset($record['coin']) ? $record['coin'] : null;
                $data['orig_regDate'] = isset($record['regDate']) ? date('Y-m-d H:i:s', strtotime($record['regDate'])) : null;
                // //default data
                $data['external_uniqueid'] = $data['seq_id'];
                $data['response_result_id'] = $extra['response_result_id'];
                $gameRecords[$index] = $data;
                unset($data);

            }
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){

        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_game_logs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_game_logs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
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


         /* queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='hd.regDate >= ? AND hd.regDate <= ?';
        // if($use_bet_time){
        //     $sqlTime='hd.regDate >= ? AND hd.regDate <= ?';
        // }

        $sql = <<<EOD
SELECT
hd.id as sync_index,
hd.response_result_id,
hd.external_uniqueid,
hd.md5_sum,

hd.seq_id as round_number,
hd.user_id as player_username,
hd.gameName as game_name,
hd.gameName as game_code,
hd.slotType,
hd.status,
hd.bet as bet_amount,
hd.bet as real_betting_amount,
hd.win as result_amount,
hd.jackpot,
hd.regDate as bet_at,
hd.regDate as start_at,
hd.regDate as end_at,
hd.ip,
hd.freeWin,
hd.startCash as before_balance,
hd.endCash as after_balance,
hd.coin,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM $this->original_game_logs_table as hd
LEFT JOIN game_description as gd ON hd.gameName = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON hd.user_id = game_provider_auth.login_name
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

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'] - $row['bet_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
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
            'bet_details' => [],
            'extra' => [],
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

        $status = $this->getGameRecordStatus($row['status']);
        $row['status'] = $status;

    }

    private function getGameRecordStatus($status) {

        $this->CI->load->model(array('game_logs'));
        switch ($status) {
            case "Normal":
                $status = Game_logs::STATUS_SETTLED;
                break;
            case "Fail":
                $status = Game_logs::STATUS_VOID;
                break;
            default:
                $status = Game_logs::STATUS_SETTLED;
                break;
        }

        return $status;

    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function isTokenValid(&$token) {
        if(!$token) {
            return false;
        }

        if($token['expireTime']==null || $token['expireTime'] > $this->CI->utils->getNowForMysql()){
            return true;
        }

        return false;
    }
}