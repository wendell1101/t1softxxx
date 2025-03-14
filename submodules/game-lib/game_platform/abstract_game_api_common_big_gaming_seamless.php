<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_big_gaming.php';
require_once dirname(__FILE__) . '/common_seamless_utils.php';

/**
 * Ticket Number: OGP-19292
 * Wallet Type(Transfer/Seamless) : Seamless
 *
 * @see Operation instruction document V1.2.8.8
 * @see Big Gaming open platform API documentation
 *
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Abstract_game_api_common_big_gaming_seamless extends Abstract_game_api_common_big_gaming
 {
    use common_seamless_utils;

    /** default original game logs table @var const*/
    const OGL = 'seamless_wallet_transactions_5800';
    const CANCELLED_STATUS = [5,6,7];

    /**
     * Determine if API call is not REST,SOAP or XMLRPC
     *
     * @var boolean|false $isHttpCall
    */
    protected $isNotHttpCall = false;

    /**
     * HTTP verb
     * @var string
    */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * HTTP statuses
     * @var string
    */
    const HTTP_STATUS_SUCCESS = 200;

    /**
     * The current method executed
     * @var string $method
     */
    protected $method;

    /**
     * URI MAP of Game API Endpoints
     *
     * @var const URI_MAP
     */
    const API_createAgentToGameProvider = 'open.agent.create';
    const URI_MAP = [
        self::API_queryForwardGame => '',
        self::API_createPlayer => 'open.user.create',
        self::API_createAgentToGameProvider => 'open.agent.create',
        self::API_syncGameRecords => 'open.order.query'
    ];

    const MD5_FIELDS_FOR_ORIGINAL=[
        'b_amount',
        'order_status',
        'valid_bet',
        'payment',
        'a_amount',
        'valid_amount',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS=[
        'b_amount',
        'valid_bet',
        'payment',
        'a_amount',
        'valid_amount',
    ];

    # Fields in game_logs table, we want to detect changes for merge, and when .md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'bet_amount',
        'status',
        'result_amount',
        'game_code',
        'player_id'
     ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'result_amount',
        'bet_amount',
        //'after_balance'
    ];

    /** transaction type */
    // const ROLLBACK_TRANS = 'refund';
    // const BET_TRANS = 'bet';
    // const WIN_TRANS = 'win';

    /**
     * Model To auto Load in construct
     *
     * @var array $modelToLoad
    */
    protected  $modelToLoad = [
        'common_seamless_wallet_transactions'
    ];

    public function __construct()
    {
        parent::__construct();

        /** Extra Info */
        $this->apiUrl = $this->getSystemInfo('url');
        $this->secret = $this->getSystemInfo('secret');
        $this->sn = $this->getSystemInfo('sn','ag08');
        $this->agentPassword = $this->getSystemInfo('agentPassword','123456');
        $this->agentLoginId = $this->getSystemInfo('agentLoginId','t1testAgent');
        $this->gameLanguage = $this->getSystemInfo('gameLanguage','th_TH');
        //$this->originalGameLogsTable = $this->getSystemInfo('originalGameLogsTable',self::OGL);
        $this->returnUrl = $this->getSystemInfo('returnUrl','/');
        $this->jsonrpc = $this->getSystemInfo('jsonrpc','2.0');
        $this->callbackSleep = $this->getSystemInfo('callbackSleep',0);
        $this->disableFishing = $this->getSystemInfo('disableFishing',true);
        $this->useTransactionAsLogs = $this->getSystemInfo('useTransactionAsLogs',false);
        $this->pageSize = $this->getSystemInfo('pageSize',100);
        $this->force_stop_limit = $this->getSystemInfo('force_stop_limit',100);
        $this->delay_response = $this->getSystemInfo('delay_response',0);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+1 day');

        /** Other Settings */
        $this->currentAPI = null; # default as null
        $this->maxChunkIdsInGameSync = $this->getSystemInfo('maxChunkIdsInGameSync',500);

        /** Load Model Here */
        $this->loadModel($this->modelToLoad);

    }

    /**
     * Determine if the Game API is Seamless or Transfer Wallet
     *
     * @return boolean
    */
    public function isSeamLessGame()
    {
        return true;
    }

    /**
     * Get API call Type
     *
     * @param string $apiName
     * @param array $params
     *
     * @return int
    */
    protected function getCallType($apiName,$params)
    {
        if($this->isNotHttpCall){
            return self::CALL_TYPE_SOAP;
        }

        return self::CALL_TYPE_HTTP;
    }

    protected function customHttpCall($ch, $params)
    {
        switch($this->method){
            case self::METHOD_POST:
                $json_data = json_encode($params);
                curl_setopt($ch,CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$json_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            break;
        }
        $this->CI->utils->debug_log(__METHOD__.' REQUEST FIELD ',json_encode($params));
    }

    /**
     * Abstract in Parent Class
     * Generate API URL
     *
     *
     * @param string $apiName
     * @param array $params
     *
     * @return
     */
    public function generateUrl($apiName,$params)
    {
        $url = rtrim($this->apiUrl,'/');

        $this->logThis(__METHOD__ .' url >>>>>>>>',$url);

        return $url;
    }

    /**
     * If your API required some headers on every request, we can add it to this method
     *
     *
     * @param array $params
     *
     * @return array $headers the headers of your request store in key => value pair
     */
    protected function getHttpHeaders($params)
    {
        $headers['Content-Type'] = 'application/json';

        return $headers;
    }

    /**
     * Abstract in Parent Class
     * Constant in apis.php, Game API unique ID
     *
     * @return array
    */
    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    /**
     * Create Player to Game Provider or in our Database
     *
     *
     * @param string $playerName
     * @param int $playerId
     * @param string $password
     * @param string $email
     * @param array $extra
     *
     * @return mixed
    */
    public function createPlayer($playerName,$playerId,$password,$email = null,$extra = null)
    {

        # it will create record in db, but if already exists, nothing happen
        parent::createPlayer($playerName,$playerId,$password,$email,$extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];
        $random = uniqid();
        $id = mt_rand(1000,9999);
        $secretCode = base64_encode(sha1($this->agentPassword,true));
        $digest = md5($random.$this->sn.$secretCode);
        $params = [
            'id' => $id,
            'method' => self::URI_MAP[self::API_createPlayer],
            'params' => [
                'random' => $random,
                'digest' => $digest,
                'sn' => $this->sn,
                'loginId' => $gameUsername,
                'agentLoginId' => $this->agentLoginId
            ],
            "jsonrpc" => $this->jsonrpc
        ];
        $this->method = self::METHOD_POST;

        $this->CI->utils->debug_log(__METHOD__.' Params',$params);

        return $this->callApi(self::API_createPlayer,$params,$context);

    }

    public function processResultForCreatePlayer($params)
    {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params,'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $httpStatusCodeResponse = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultArr,$httpStatusCodeResponse,self::API_createPlayer,$gameUsername);
        $result = ['response_result_id'=>$responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId,Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        return [$success,$result];
    }

    /**
     * Create Agent to Game Provider
     *
     *
     * @param string $loginId
     * @param string $password
     *
     * @return mixed
    */
    public function createAgentToGameProvider($loginId,$password)
    {

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateAgentToGameProvider',
        ];
        $random = uniqid();
        $id = mt_rand(1000,9999);
        $secretCode = base64_encode(sha1($this->agentPassword,true));
        $digest = md5($random.$this->sn.$secretCode);
        $params = [
            'id' => $id,
            'method' => self::URI_MAP[self::API_createAgentToGameProvider],
            'params' => [
                'random' => $random,
                'sign' => md5($random.$this->sn.$loginId.$this->secret),
                'sn' => $this->sn,
                'loginId' => $loginId,
                'password' => $password
            ],
            "jsonrpc" => $this->jsonrpc
        ];
        $this->method = self::METHOD_POST;

        $this->CI->utils->debug_log(__METHOD__.' Params',$params);

        return $this->callApi(self::API_createAgentToGameProvider,$params,$context);

    }

    public function processResultForCreateAgentToGameProvider($params)
    {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = null;
        $httpStatusCodeResponse = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultArr,$httpStatusCodeResponse,self::API_createAgentToGameProvider,$gameUsername);
        $result = ['response_result_id'=>$responseResultId];

        return [$success,$result];
    }

    /**
     * Abstract in Parent Class,Since this is seamless, the transaction like deposit is only save in table playeraccount
     *
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     *
     * @return
    */
    public function depositToGame($playerName,$amount,$transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        $this->logThis(__METHOD__ .' player name is: >>>>>>>>',$playerName);

        return [
           "success" => true,
           "external_transaction_id" => $external_transaction_id,
           "response_result_id" => null,
           "didnot_insert_game_logs" => true
        ];
    }

    /**
     * Abstract in Parent Class,Since this is seamless, the transaction like deposit is only save in table playeraccount
     *
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     *
     * @return
     */
    public function withdrawFromGame($playerName,$amount,$transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        $this->logThis(__METHOD__ .' player name is: >>>>>>>>',$playerName);

        return [
           "success" => true,
           "external_transaction_id" => $external_transaction_id,
           "response_result_id" => null,
           "didnot_insert_game_logs" => true
        ];
    }

    /**
     * Abstract in Parent Class
     *
     * @param $playerName
     *
     * @return
    */
    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

        $balance = $this->getPlayerSubWalletBalance($playerId);

        if(! is_null($balance)){
            return [
                'success' => true,
                'balance' => $balance
            ];
        }

        return [
            'success' => false,
            'balance' => $balance
        ];
    }

    /**
     * Abstract in Parent Class
     *
     * @param string $transactionId
     * @param array $extra
     *
     * @return
     */
    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }


    /**
     * Abstract in Parent Class
     *
     *
     * @param string $playerName
     * @param array $extra
     *
     * @return
    */
    public function queryForwardGame($playerName, $extra)
    {

        if(!$this->validateWhitePlayer($playerName)){
            return array('success' => false);
        }

        $this->CI->utils->debug_log('BIGGAMING SEAMLESS '. __METHOD__, 'playerName', $playerName, 'extra', $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $mode = 'real';
        if( isset($extra['game_mode']) && in_array($extra['game_mode'], ['demo','trial','fun'])){
            $mode = 'trial';
        }

        $gameType = 'live_dealer';
        if(isset($extra['game_type']) && !empty($extra['game_type'])){
            $gameType = $extra['game_type'];
        }

        $isMobileUrl = 0;
        if(isset($extra['is_mobile']) && $extra['is_mobile']){
            $isMobileUrl = 1;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $random = uniqid();
        $id = mt_rand(1000,9999);
        $secretCode = base64_encode(sha1($this->agentPassword,true));


        $params = [
            'id' => $id,
            'method' => $this->getGameLaunchMethod($gameType,$mode),
            'params' => [
                'sn' => $this->sn,
                'loginId' => $gameUsername,
                'locale' => $this->gameLanguage,
                'random' => $random,
                //'digest' => $digest,
                'isMobileUrl' => $isMobileUrl,
                'returnUrl' => $this->returnUrl
            ],
            "jsonrpc" => $this->jsonrpc
        ];

        if($gameType=='fishing_game'){
            $digest = md5($random.$this->sn.$this->secret);
            if($mode=='trial'){
                $digest = md5($random.$this->sn.$secretCode);
            }
            $params['params']['sign'] = $digest;
        }else{
            if($mode=='trial'){
                $digest = md5($random.$this->sn.$this->secret);
                $params['params']['sign'] = $digest;
                unset($params['params']['loginId']);
            }else{
                $digest = md5($random.$this->sn.$gameUsername.$secretCode);
                $params['params']['digest'] = $digest;
            }
        }

        $context['params'] = $params;

        $this->method = self::METHOD_POST;

        $this->CI->utils->debug_log('BIGGAMING SEAMLESS '. __METHOD__, 'params', $params);

        return $this->callApi(self::API_queryForwardGame,$params,$context);
    }

    public function getGameLaunchMethod($gameType, $mode='real'){
        if($mode == 'real'){
            switch ($gameType) {
                case 'fishing_game';
                    if(!$this->disableFishing){
                        return 'open.game.bg.fishing.url';
                    }
                    return 'open.video.game.url';
                    break;
                case 'lottery';
                    return 'open.lottery.game.url';
                    break;
                case 'live_dealer';
                default:
                    return 'open.video.game.url';
            }
        }else{
            switch ($gameType) {
                case 'fishing_game';
                    return 'open.game.bg.fishing.trial.url';
                    break;
                case 'lottery';
                    return 'open.lottery.trial.game.url';
                    break;
                case 'live_dealer';
                default:
                    return 'open.video.trial.game.url';
            }
        }

    }

    /**
     * Process queryForwardGame response
    */
    public function processResultforQueryForwardGame($params)
    {
        $this->CI->utils->debug_log('BIGGAMING SEAMLESS '. __METHOD__, 'params', $params);

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $launch_params = $this->getVariableFromContext($params, 'params');

        $httpStatusCodeResponse = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultArr,$httpStatusCodeResponse,self::API_queryForwardGame,$gameUsername);
        $result = [
            'response_result_id' => $responseResultId
        ];

        $this->CI->utils->debug_log('BIGGAMING SEAMLESS '. __METHOD__, 'result', $result);

        if($success){
            if(isset($resultArr['result'])){
                $result['url'] = $resultArr['result'];
            }else{
                // missing game url
                $success = false;
            }
        }

        return [$success,$result];
    }

    /**
     * Abstract in Parent Class
     * Sync Original Game Logs
     *
     * @param string $token token from sync Information, found in \Game_platform_manager::class@syncGameRecordsNoMergeOnOnePlatform
     *
     * @return
     */
    public function syncOriginalGameLogs($token)
    {
        //initialize variable
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $startDate->modify($this->getDatetimeAdjust());
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

        $this->rowsCount = $cnt = 0;
        $success = true;

        $this->method = self::METHOD_POST;
        $this->currentAPI = self::API_syncGameRecords;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

		while ($queryDateTimeMax  > $queryDateTimeStart) {

            $done = false;
            $this->maxPage = $currentPage = 1;

            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }

            $random = uniqid();
            $id = mt_rand(1000,9999);
            $secretCode = base64_encode(sha1($this->agentPassword,true));

            while (!$done) {
                $this->utils->debug_log("############# BIGGAMING SEAMLESS: (syncOriginalGameLogs) queryDateTimeStart:",$queryDateTimeStart," queryDateTimeEnd: ", $queryDateTimeEnd, 'currentPage',$currentPage);
                $params = [
                    'id' => $id,
                    'method' => self::URI_MAP[self::API_syncGameRecords],
                    'params' => [
                        'random' => $random,
                        'sign' => md5($random.$this->sn.$this->secret),
                        'sn' => $this->sn,
                        'startTime' => $startDateParam->format('Y-m-d H:i:s'),
                        'endTime' => $endDateParam->format('Y-m-d H:i:s'),
                        'pageIndex' => $currentPage,
                        'pageSize' =>  $this->pageSize
                    ],
                    "jsonrpc" => $this->jsonrpc
                ];
                $rlt = $this->callApi(self::API_syncGameRecords, $params, $context);
                $this->utils->debug_log("############# BIGGAMING SEAMLESS: (syncOriginalGameLogs) rlt:",$rlt);

                $currentPage++;
				if ($currentPage>$this->maxPage || $currentPage>$this->force_stop_limit) {
					$done = true;
                }

                sleep($this->common_wait_seconds);
            }//end while for page

            $queryDateTimeStart = $endDateParam->format('Y-m-d H:i:s');
	    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

        }//end while for date


        return [
            'success' => $success,
            'rows_count' => $this->rowsCount
        ];
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $httpStatusCodeResponse = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $httpStatusCodeResponse, self::API_syncGameRecords);
        $gameRecords = isset($arrayResult['result']['items']) && !empty($arrayResult['result']['items']) ? $arrayResult['result']['items']:[];

        $this->utils->debug_log("############# BIGGAMING SEAMLESS: (syncOriginalGameLogs) arrayResult2:",
        $arrayResult['result']['items'],
        $arrayResult['result']['total'],
        $arrayResult['result']['pageSize'],
        $arrayResult['result']['pageIndex']);

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false,
            'row_count' => 0
        );

        if($success && !empty($gameRecords)) {

            $totalRows = isset($arrayResult['result']['total']) ? $arrayResult['result']['total'] : 0;
            $pageSize = isset($arrayResult['result']['pageSize']) ? $arrayResult['result']['pageSize'] : 0;
            $currentPage = isset($arrayResult['result']['pageIndex']) ? $arrayResult['result']['pageIndex'] : 0;
            if($totalRows<$pageSize){
                $this->maxPage = $currentPage;
            }
            $this->CI->utils->info_log(__METHOD__.' totalItems >>>>>>>',$totalRows);

            # check if data is more than page limit
            if($totalRows>0){
                $extra = ['response_result_id'=>$responseResultId];
                $this->rebuildGameRecords($gameRecords,$extra);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
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

                if (!empty($insertRows)) {
                    $dataResult['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $dataResult['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($updateRows);
            }

            $dataResult['is_max_return'] = false;
            $dataResult['row_count'] = $totalRows;
            $this->rowsCount+= $totalRows;

        }

        return array($success, $dataResult);
    }


    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    private function rebuildGameRecords(&$gameRecords,$extra){

		$this->CI->utils->debug_log('BIGGAMING SEAMLESS (rebuildGameRecords)', $gameRecords);

		$new_gameRecords =[];
        if(isset($gameRecords)){

            foreach($gameRecords as $index => $record) {
                $new_gameRecords[$index]['tran_id'] = isset($record['tranId'])?$record['tranId']:null;
                $new_gameRecords[$index]['a_amount'] = isset($record['aAmount'])?$record['aAmount']:null;
                $new_gameRecords[$index]['login_id'] = isset($record['loginId'])?$record['loginId']:null;
                $new_gameRecords[$index]['order_id'] = isset($record['orderId'])?$record['orderId']:null;
                $new_gameRecords[$index]['module_name'] = isset($record['moduleName'])?$record['moduleName']:null;
                $new_gameRecords[$index]['order_status'] = isset($record['orderStatus'])?$record['orderStatus']:null;
                $new_gameRecords[$index]['play_id'] = isset($record['playId'])?$record['playId']:null;
                $new_gameRecords[$index]['uid'] = isset($record['uid'])?$record['uid']:null;
                $new_gameRecords[$index]['order_time'] = isset($record['orderTime'])?$record['orderTime']:null;
                $new_gameRecords[$index]['game_name'] = isset($record['gameName'])?$record['gameName']:null;
                $new_gameRecords[$index]['payment'] = isset($record['payment'])?$record['payment']:null;
                $new_gameRecords[$index]['sn'] = isset($record['sn'])?$record['sn']:null;
                $new_gameRecords[$index]['b_amount'] = isset($record['bAmount'])?$record['bAmount']:null;
                $new_gameRecords[$index]['module_id'] = isset($record['moduleId'])?$record['moduleId']:null;
                $new_gameRecords[$index]['no_comm'] = isset($record['noComm'])?$record['noComm']:null;
                $new_gameRecords[$index]['game_id'] = isset($record['gameId'])?$record['gameId']:null;
                $new_gameRecords[$index]['play_name_en'] = isset($record['playNameEn'])?$record['playNameEn']:null;
                $new_gameRecords[$index]['issue_id'] = isset($record['issueId'])?$record['issueId']:null;
                $new_gameRecords[$index]['play_name'] = isset($record['playName'])?$record['playName']:null;
                $new_gameRecords[$index]['user_id'] = isset($record['userId'])?$record['userId']:null;
                $new_gameRecords[$index]['valid_amount'] = isset($record['validAmount'])?$record['validAmount']:null;
                $new_gameRecords[$index]['game_name_en'] = isset($record['gameNameEn'])?$record['gameNameEn']:null;
                $new_gameRecords[$index]['from_ip'] = isset($record['fromIp'])?$record['fromIp']:null;
                $new_gameRecords[$index]['table_id'] = isset($record['tableId'])?$record['tableId']:null;
                $new_gameRecords[$index]['order_from'] = isset($record['orderFrom'])?$record['orderFrom']:null;
                //$new_gameRecords[$index]['bet_content'] = isset($record['betContent'])?$record['betContent']:null;
                $new_gameRecords[$index]['bet_content'] = null;
                $new_gameRecords[$index]['valid_bet'] = isset($record['validBet'])?$record['validBet']:null;
                $new_gameRecords[$index]['last_update_time'] = isset($record['lastUpdateTime'])?$record['tranId']:null;

                $new_gameRecords[$index]['external_uniqueid'] = isset($record['orderId'])?$record['orderId']:null;
                $new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
            }
        }

        $gameRecords = $new_gameRecords;
	}

    /**
     * Abstract in Parent Class
     * Sync Merge Game Logs
     *
     * @param string $token token from sync Information, found in \Game_platform_manager::class@syncGameRecordsNoMergeOnOnePlatform
     *
     * @return
     */
    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /**
     * Query Original Game Logs for Merging
     *
     * @param string $dateFrom where the date start for sync original
     * @param string $dataTo where the date end
     *
     * @return array
    */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        $this->CI->utils->debug_log('BIGGAMING SEAMLESS sqlTime ===>', $sqlTime);


        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.response_result_id,
	original.order_id as table_id,
    original.order_id as round,
    original.login_id as username,
    original.b_amount as bet_amount,
    original.payment as result_amount,
	original.order_time as start_at,
    original.order_time as end_at,
    original.order_time as bet_at,
	original.game_id as game_code,
    original.game_name_en as game_name,
	original.updated_at,
	original.external_uniqueid,
	original.order_status as order_status,
    original.md5_sum,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.login_id = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('OGPLUS (queryOriginalGameLogs) sql:', $sql);

		$this->CI->utils->debug_log('OGPLUS (queryOriginalGameLogs) params: ', $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $bet_amount = abs($row['bet_amount']);
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $this->gameTimeToServerTime($row['bet_at']),
                'end_at' => $this->gameTimeToServerTime($row['start_at']),
                'bet_at' => $this->gameTimeToServerTime($row['bet_at']),
                'updated_at' => $row['updated_at']
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
            'bet_details' => [],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
        if(in_array((int)$row['order_status'],self::CANCELLED_STATUS)){
            $row['status'] = Game_logs::STATUS_CANCELLED;
        }

        $row['bet_details'] = [];
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    /**
     * Change the password of player in our SBE
     *
     * @param string $playerName
     * @param string $oldPassword
     * @param string $newPassword
     *
     * @return array
    */
    public function changePassword($playerName, $oldPassword, $newPassword)
    {
        return $this->returnUnimplemented();
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => $success);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => $success);
    }

    /** UTILS */

    /**
     * Process The response of Game Provider if true or false, true = success,false = error
     *
     * @param int $responseResultId
     * @param mixed $apiResult
     * @param string $gameUsername
     *
     * @return boolean
     */
    public function processResultBoolean($responseResultId,$apiResult,$statusCode,$api=null,$gameUsername = null)
    {
        $success = false;

        // # for create player
        // 2206 = means player already exist, accept it as success
        if($api == self::API_createPlayer){
            if(isset($apiResult['result']['success']) && $apiResult['result']['success'] == "true"){
                $success = true;
            }
            if(isset($apiResult['error']['code']) && $apiResult['error']['code'] == 2206){
                $success = true;
            }
        }

        if($api == self::API_queryForwardGame){
            if(isset($apiResult['result'])){
                $success = true;
            }
        }

        if($statusCode != self::HTTP_STATUS_SUCCESS){
            $success = false;
        }
        $this->CI->utils->debug_log('bermar', $api, self::API_syncGameRecords, $apiResult);
        if($api == self::API_syncGameRecords){
            if(isset($apiResult['result']['items'])){
                $success = true;
            }
        }

        if(! $success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->error_log(__METHOD__ .' BIG GAMING GAME Got Error! =========================> ',$responseResultId,$apiResult);
        }

        return $success;
    }

    /**
     * Process Game Language of the game
     *
     * @param mixed $currentLang
     *
     * @return string $language
    */
    public function getLauncherLanguage($currentLang, $isFishing = false)
    {
        $this->CI->load->library(array('language_function'));

       switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
                $language = 'cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi":
                $language = 'en'; // not supported
                break;
            case "en":
                $language = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
                $language = 'en'; // not supported
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
$table = $this->getSeamlessTransactionTable();
//$this->CI->utils->error_log(__METHOD__ .' bermar trans table used =========================> ',$table);
$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.bet_amount bet_amount,
t.result_amount result_amount,
t.extra_info extra_info
FROM {$table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $this->transGameTimeToServerTime($transaction['transaction_date']);
                $temp_game_record['amount'] = abs($transaction['amount']);

                $betAmount = abs($transaction['bet_amount']);
                $resultAmount = abs($transaction['result_amount']);

                //if >0 meaning its sub orders
                if($betAmount>0 && $transaction['trans_type']=='bet'){
                    $temp_game_record['amount'] = 0;
                }

                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                if(empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])){
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                $extra_info = @json_decode($transaction['extra_info'], true);
                $extra_info_params = isset($extra_info['params'])?$extra_info['params']:[];
                $orders = isset($extra_info_params['orders'])?$extra_info_params['orders']:[];
                $extra=[];
                $extra['trans_type'] = ($transaction['trans_type']=='bet')?'debit':'credit';
                $extra['items'] = $orders;

                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getTransactionsTable($monthStr = null){
        return 'seamless_wallet_transactions_5800';
    }

    public function getSeamlessTransactionTable(){
        return $this->getTransactionsTable();
    }
    
    public function getTransGameTimeToServerTime() {
        return $this->getSystemInfo('transGameTimeToServerTime','+0 hours');
    }

    public function transGameTimeToServerTime($dateTimeStr) {
        if (is_object($dateTimeStr) && $dateTimeStr instanceof DateTime) {
            $dateTimeStr = $dateTimeStr->format('Y-m-d H:i:s');
        }
        $modify = $this->getTransGameTimeToServerTime();
        return $this->utils->modifyDateTime($dateTimeStr, $modify);
    }

 }