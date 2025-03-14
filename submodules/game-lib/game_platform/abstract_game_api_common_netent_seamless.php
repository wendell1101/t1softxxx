<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/common_seamless_utils.php';

/**
 * Ticket Number: OGP-18063
 * Wallet Type(Transfer/Seamless) : Seamless
 * 
 * @see https://developer.netent.com/
 * @see Password: NetEntIntegration123
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Abstract_game_api_common_netent_seamless extends Abstract_game_api
 {
    use common_seamless_utils;

    /** default original game logs table @var const*/
    const OGL = 'common_seamless_wallet_transactions';

    /** 
     * Determine if API call is not REST,SOAP or XMLRPC
     * 
     * @var boolean|true $isHttpCall
    */
    protected $isNotHttpCall = true;

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
     * URI MAP of Game API Endpoints
     * 
     * @var const URI_MAP
     */
    const URI_MAP = [
    ];

    /**
     * REST URI MAP of Game API Endpoints
     * 
     * @var const JSON_URI_MAP
     */
    const JSON_URI_MAP = [
    ];

    # Fields in game_logs table, we want to detect changes for merge, and when .md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'transaction_type',
        'status',
        'result_amount',
        'game_code',
        'player_id',
        'bet_amount',
        'after_balance',
        'roundId'
     ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'result_amount',
        'bet_amount',
        'after_balance'
    ];

    /** transaction type */
    const ROLLBACK_TRANS = 'refund';
    const BET_TRANS = 'bet';
    const WIN_TRANS = 'win';

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
        $this->gameLanguage = $this->getSystemInfo('gameLanguage');
        $this->originalGameLogsTable = $this->getSystemInfo('originalGameLogsTable',self::OGL);
        $this->jsonApiUrl = $this->getSystemInfo('jsonApiUrl','');
        $this->casinoId = $this->getSystemInfo('casinoId','wnentertainment-api-test');
        $this->callerId = $this->getSystemInfo('callerId','scs188staging');
        $this->callerPassword = $this->getSystemInfo('callerPassword','9xfzf85FdmiNcFEx');
        $this->merchantId = $this->getSystemInfo('merchantId','testmerchant');
        $this->merchantPassword = $this->getSystemInfo('merchantPassword','testing');
        $this->useLocalWsdlFile = $this->getSystemInfo('useLocalWsdlFile',true);
        $this->wsdlPathId = $this->getSystemInfo('wsdlPathId',2297);
        $this->wsdlFileName = $this->getSystemInfo('wsdlFileName','netent_seamless_staging_server.wsdl');
        $this->wsdlCdn = $this->getSystemInfo('wsdlCdn', "https://{$this->casinoId}.casinomodule.com/ws-jaxws/services/casino?wsdl");
        $this->country = $this->getSystemInfo('country','TH');
        $this->casinoBrand = $this->getSystemInfo('casinoBrand','wnentertainment');
        $this->staticServerURL = $this->getSystemInfo('staticServerURL','https://wnentertainment-static-test.casinomodule.com');
        $this->gameServerURL = $this->getSystemInfo('gameServerURL','https://wnentertainment-game-test.casinomodule.com');
        $this->gameJsUrl = $this->getSystemInfo('gameJsUrl','https://wnentertainment-static-test.casinomodule.com/gameinclusion/library/gameinclusion.js');

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
        if(in_array($apiName,self::JSON_URI_MAP)){

            $apiUri = self::URI_MAP[$apiName];

            return rtrim($this->apiUrl,'/').$apiUri;
        }

        if($this->useLocalWsdlFile){
            ini_set("soap.wsdl_cache_enabled", 0);
            return realpath(dirname(__FILE__)).'/wsdl'.'/'.$this->wsdlPathId.'/'.$this->wsdlFileName;
        }else{
            return $this->apiUrl;
        }
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
        $headers['Content-Type'] = 'application/xml';

        return $headers;
    }

    /**
     * Generate SOAP method
     * 
     * @param string $apiName
     * @param array params
     * 
     * @return array
     */
    protected function generateSoapMethod($apiName,$params)
    {
        switch($apiName){
            case self::API_createPlayer:
                return array('loginUserDetailed', $params);
            break;
            case self::API_queryForwardGame:
                return array('loginUserDetailed', $params);
            break;
        }

        return parent::generateSoapMethod($apiName, $params);
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
     * TODO: other requirements
     * 
     * @param string $playerName
     * @param int $playerId
     * @param string $password
     * @param string $email
     * @param array $extra
     * 
     * @return mixed
    */
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerPassword' => $password,
        ];

        # channel default to bbg (browser based games), it will override to correct channel in queryForwardGame
        $params = [
            "userName" => $gameUsername,
            "extra" => ["country",$this->country,"channel","bbg"],
            "merchantId" => $this->merchantId,
            "merchantPassword" => $this->merchantPassword,
            "currencyISOCode" => $this->currency
        ];

        $this->CI->utils->debug_log(__METHOD__.' params', $params);

        return  $this->callApi(self::API_createPlayer, $params, $context);

    }

    /** 
     * Process createPlayer Response
    */
    public function processResultForCreatePlayer($response)
    {
        $responseResultId = $this->getResponseResultIdFromParams($response);
        $playerId = $this->getVariableFromContext($response, 'playerId');
        $gameUsername = $this->getVariableFromContext($response, 'gameUsername');
        $resultObj = $this->getResultObjFromParams($response);
        $arrayResult = json_decode(json_encode($resultObj),true);
        $httpStatusCodeResponse = $this->getStatusCodeFromParams($response);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$httpStatusCodeResponse,self::API_createPlayer,$gameUsername);

        $result = ['response_result_id' => $responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        $this->CI->utils->debug_log(__METHOD__ . " response raw",$arrayResult,'response array',$arrayResult,'success',$success,'http response status code',$httpStatusCodeResponse);

        return [$success, $result];
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
        $playerCurrentToken = $this->getPlayerTokenByUsername($playerName);
        $isTokenValid = $this->isTokenValid($playerCurrentToken);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $is_mobile = isset($extra['is_mobile']) ? $extra['is_mobile'] : false;
        $channel = $is_mobile ? "mobg" : "bbg";
        $gameMode =  isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $gameId =  isset($extra['game_code']) ? $extra['game_code'] : null;
        $language = isset($extra['language']) ? $extra['language'] : null;
        $lang = $this->getLauncherLanguage($language);
        $lobbyUrl = $this->getHomeLink();

        if($isTokenValid){

            $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultforQueryForwardGame',
                'playerName' => $playerName,
                'gameUsername' => $gameUsername,
                'gamePlatformMode' => $channel,
                'gameMode' => $gameMode,
                'lang' => $lang,
                'lobbyUrl' => $lobbyUrl,
                'casinoBrand' => $this->casinoBrand,
                'gameId' => $gameId,
                'staticServerURL' => $this->staticServerURL,
                'gameServerURL' => $this->gameServerURL,
                'gameJsUrl' => $this->gameJsUrl
            ];

            $params = [
                "userName" => $gameUsername,
                "extra" => ["country",$this->country,"channel",$channel,"ServerToken",$playerCurrentToken],
                "merchantId" => $this->merchantId,
                "merchantPassword" => $this->merchantPassword,
                "currencyISOCode" => $this->currency
            ];

            $this->CI->utils->debug_log(__METHOD__.' params', $params);

            return  $this->callApi(self::API_queryForwardGame, $params, $context);
        }else{
            return [
                'success' => false,
                'url' => null
            ];
        }
    }

    /** 
     * Process queryForwardGame response
    */
    public function processResultforQueryForwardGame($response)
    {
        $responseResultId = $this->getResponseResultIdFromParams($response);
        $gameUsername = $this->getVariableFromContext($response, 'gameUsername');
        $gamePlatformMode = $this->getVariableFromContext($response, 'gamePlatformMode');
        $gameMode = $this->getVariableFromContext($response, 'gameMode');
        $gameId = $this->getVariableFromContext($response, 'gameId');
        $staticServerURL = $this->getVariableFromContext($response, 'staticServerURL');
        $gameServerURL = $this->getVariableFromContext($response, 'gameServerURL');
        $gameJsUrl = $this->getVariableFromContext($response, 'gameJsUrl');
        $lobbyUrl = $this->getVariableFromContext($response, 'lobbyUrl');
        $casinoBrand = $this->getVariableFromContext($response, 'casinoBrand');
        $lang = $this->getVariableFromContext($response, 'lang');
        $resultObj = $this->getResultObjFromParams($response);
        $arrayResult = json_decode(json_encode($resultObj),true);
        $httpStatusCodeResponse = $this->getStatusCodeFromParams($response);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$httpStatusCodeResponse,self::API_queryForwardGame,$gameUsername);
        $sessionId = isset($arrayResult['loginUserDetailedReturn']) ? $arrayResult['loginUserDetailedReturn'] : null;

        $result = [
            'response_result_id' => $responseResultId,
            'sessionId' => $sessionId,
            'gamePlatformMode' => $gamePlatformMode,
            'gameMode' => $gameMode,
            'lobbyUrl' => $lobbyUrl,
            'casinoBrand' => $casinoBrand,
            'lang' => $lang,
            'gameId' => $gameId,
            'staticServerURL' => $staticServerURL,
            'gameServerURL' => $gameServerURL,
            'gameJsUrl' => $gameJsUrl
        ];

        $this->CI->utils->debug_log(__METHOD__ . " response raw",$arrayResult,'response array',$arrayResult,'success',$success,'http response status code',$httpStatusCodeResponse,'resultData',$result);


        return [
            $success,
            $result
        ];
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
        return $this->returnUnimplemented();
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
    public function queryOriginalGameLogs($dateFrom,$dateTo,$use_bet_time)
    {
        $roundIds = $this->CI->common_seamless_wallet_transactions->getRoundIDS($dateFrom,$dateTo,$this->getPlatformCode());
        $ids = array_column($roundIds,'round_id');
        $gameRecords = [];
        $mergedGameRecords = [];

        if(is_array($ids) && count($ids) > 0){
            $chunkedIds = array_chunk($ids,$this->maxChunkIdsInGameSync);
            # chunk the result, default is 500 per array
            $key = 0;
            foreach($chunkedIds as $chunkedId){

                $gameRecords = $this->CI->common_seamless_wallet_transactions->getTransactionsByRoundIds($chunkedId,$this->getPlatformCode());

                # filter here, the round id must be unique
                $ac = array_column($gameRecords,'round_id');
                $au = array_unique($ac);
                $av = array_values($au);
                $processedGameRecords = [];

                # group the bet,win and rollback if we have in one array
                foreach($gameRecords as $k1 => $v1){
                    if(isset($v1['round_id'])){
                        foreach($av as $avk1 => $avv1){
                            if(isset($v1['round_id'])){
                                if($avv1 == $v1['round_id']){
                                    $processedGameRecords[$avv1][] = $v1;
                                }
                            }
                        }
                    }
                }

                if(is_array($processedGameRecords) && count($processedGameRecords) > 0){
                    $data = [];
                    foreach($processedGameRecords as $processedGameRecordKey => $processedGameRecordValue){
                        foreach($processedGameRecordValue as $value){
                            if(isset($value['transaction_type'])){
                                # check if refund transaction
                                if($value['transaction_type'] == self::ROLLBACK_TRANS){
                                    $data['status'] = Game_logs::STATUS_CANCELLED;
                                    $data["result_amount"] = 0;
                                    $data["end_at"] = isset($value["end_at"]) ? $value["end_at"] : null;
                                }elseif($value['transaction_type'] == self::BET_TRANS){
                                    $data['game_type_id'] = isset($value["game_type_id"]) ? $value["game_type_id"] : null;
                                    $data['game_description_id'] = isset($value["game_description_id"]) ? $value["game_description_id"] : null;
                                    $data['game_code'] = isset($value["game_id"]) ? $value["game_id"] : null;
                                    $data['game'] = isset($value["game_id"]) ? $value["game_id"] : null;
        
                                    $data['player_id'] = isset($value["player_id"]) ? $value["player_id"] : null;
                                    $data['player_username'] = isset($value["player_username"]) ? $this->getPlayerUsernameByGameUsername($value["player_username"]) : null;
        
                                    $data['bet_amount'] = isset($value["amount"]) ? $value["amount"] : null;
                                    $data['result_amount'] = isset($value["amount"]) ? -abs($value["amount"]) : null;
                                    $data['bet_for_cashback'] = isset($value["amount"]) ? $value["amount"] : null;
                                    $data['real_betting_amount'] = isset($value["amount"]) ? $value["amount"] : null;
                                    $data['after_balance'] = isset($value["after_balance"]) ? $value["after_balance"] : null;
        
                                    $data['start_at'] = isset($value["start_at"]) ? $value["start_at"] : null;
                                    $data['end_at'] = isset($value["end_at"]) ? $value["end_at"] : null;
                                    $data['bet_at'] = isset($value["start_at"]) ? $value["start_at"] : null;
        
                                    $data["status"] = Game_logs::STATUS_PENDING;
        
                                    $data["response_result_id"] = isset($value["response_result_id"]) ? $value["response_result_id"] : null;
                                    $data["external_uniqueid"] = isset($value["external_uniqueid"]) ? $value["external_uniqueid"] : null;
                                    $data["roundId"] = isset($value["round_id"]) ? $value["round_id"] : null;
                                    $data["md5_sum"] = null;
                                    $data["sync_index"] = isset($value["sync_index"]) ? $value["sync_index"] : null;
                                    $data["bet_details"] = isset($value["extra_info"]) ? $value["extra_info"] : null;
                                    $data["transaction_type"] = isset($value["transaction_type"]) ? $value["transaction_type"] : null;
                                }else{
                                    # win trans
                                    $data["result_amount"] += (isset($value["amount"]) ? $value["amount"] : null);
                                    $data["response_result_id"] = isset($value["response_result_id"]) ? $value["response_result_id"] : null;
                                    $data["external_uniqueid"] = isset($value["external_uniqueid"]) ? $value["external_uniqueid"] : null;
                                    $data["end_at"] = isset($value["end_at"]) ? $value["end_at"] : null;
                                    $data["after_balance"] = isset($value["after_balance"]) ? $value["after_balance"] : null;
                                    $data["updated_at"] = $this->CI->utils->getNowForMysql();
                                    $data["status"] = Game_logs::STATUS_SETTLED;
                                }
                            }
                        }

                        $mergedGameRecords[$key] = $data;
                        unset($data);
                        $key++;
                        $this->CI->utils->debug_log('jason',$key);
                    }
                }
            }
        }

        return $mergedGameRecords;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' => $row['roundId']
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        return [
            'game_info' => [
                'game_type_id' => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type' => null,
                'game' => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id' =>  isset($row['player_id']) ? $row['player_id'] : null,
                'player_username' => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : null,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : null,
                'bet_for_cashback' => isset($row['bet_for_cashback']) ? $row['bet_for_cashback'] : null,
                'real_betting_amount' => isset($row['real_betting_amount']) ? $row['real_betting_amount'] : null,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' =>  isset($row['after_balance']) ? $row['after_balance'] : null,
            ],
            'date_info' => [
                'start_at' => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at' => isset($row['end_at']) ? $row['end_at'] : null,
                'bet_at' => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => isset($row['status']) ? $row['status'] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => isset($row['roundId']) ? $row['roundId'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type' => null
            ],
            'bet_details' => isset($row['bet_details']) ? $row['bet_details'] : null,
            'extra' => $extra,
            // from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null     
        ];
    }

    /**
     *
     * prepare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $betDetails = [];

        if(isset($row['bet_details'])){
            $betJson = json_decode($row['bet_details'],true);
            $betDetails = [
                'reason' => isset($betJson['reason']) ? $betJson['reason'] : null,
                'round ID' =>   isset($betJson['gameRoundRef']) ? $betJson['gameRoundRef'] : null,
                'session' => isset($betJson['serverToken']) ? $betJson['serverToken'] : null,
                'request_id' => isset($row['response_result_id']) ? $row['response_result_id'] : null
            ];
        }

        $row['bet_details'] = $betDetails;
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
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
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

        # for create player
        if($api == self::API_createPlayer || $api == self::API_queryForwardGame){
            if(isset($apiResult['loginUserDetailedReturn']) && !empty($apiResult['loginUserDetailedReturn'])){
                $success = true;
            }
        }

        if($statusCode != self::HTTP_STATUS_SUCCESS){
            $success = false;
        }

        if(! $success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log(__METHOD__ .' NETENT GAME Got Error! =========================> ',$responseResultId,'gameUsername ',$gameUsername);
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
    public function getLauncherLanguage($currentLang) 
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

 }