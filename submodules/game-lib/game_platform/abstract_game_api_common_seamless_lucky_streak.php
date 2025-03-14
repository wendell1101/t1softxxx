<?php
if(! defined('BASEPATH')){
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/common_seamless_utils.php';

/**
 * 
 * 
 * @integrator jason.php.ph
 */

abstract class Abstract_game_api_common_seamless_lucky_streak extends Abstract_game_api
{
    use common_seamless_utils;

    /** 
     * Generated JWT token
     * 
     * @var string $generatedToken
    */
    protected $generatedToken = null;

    /** 
     * Determine if API call is HTTP,SOAP or XMLRPC
     * 
     * @var boolean|true $isHttpCall
    */
    protected $isHttpCall = true;

    /** 
     * Game Session URL, needed before player can launch a game
     * 
     * @var string $gameLaunchUrl
    */
    protected $gameLaunchUrl;

    /**
     * Default game language
     * 
     * @var int $defaultGameLanguage
     */
    protected $defaultGameLanguage;

    /**
     * Default game currency
     * 
     * @var int $defaultGameCurrency
     */
    protected $defaultGameCurrency = 'CNY';

    /**
     * The current method executed
     * @var string $method
     */
    protected $method;

    const ORIGINAL_TABLE = 'lucky_streak_seamless';

    const API_getTokenAndDetails = 'getTokenAndDetails';
    const API_getGameListDetails = 'getGameListDetails';
    const API_getRoundDetails = 'getRoundDetails';
    const API_getBetDetails = 'getBetDetails';

	const START_PAGE = 1;

    /**
     * URI MAP of Game API Endpoints
     * 
     * @var const URI_MAP
     */
    const URI_MAP = [
        self::API_createPlayer => '',
        self::API_getTokenAndDetails => '/connect/token',
        self::API_getGameListDetails => '/v3/Lobby/Games',
        self::API_getRoundDetails => '/rounds/rounds',
        self::API_getBetDetails => '/bets/bets',
        self::API_syncGameRecords => '/bets/bets'
    ];

    /** 
     * HTTP verb
     * @var string
    */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /** 
     * Fields in table, we want to detect changes for update in fields
     * @var constant MD5_FIELDS_FOR_ORIGINAL 
    */
    const MD5_FIELDS_FOR_ORIGINAL = [
        "betId",
        "playerId",
        "operatorId",
        "playername",
        "roundId",
        "gameId",
        "gameName",
        "gameType",
        "betAmount",
        "winAmount",
        "income",
        "betTime",
        "status",
        "sessionId",
        "extra_info",
        "external_unique_id"
     ];

   # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        "betAmount",
        "winAmount",
        "income"
     ];

    # Fields in game_logs table, we want to detect changes for merge, and when .md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'roundId',
        'game_code',
        'status',
        'bet_amount',
        'winAmount',
        'result_amount',
        'betTime'
     ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'winAmount',
        'result_amount'
    ];

    /** 
     * Model To Load
     * 
     * @var array $modelToLoad
    */
    protected  $modelToLoad = [
    ];

    public function __construct()
    {
        parent::__construct();

        /** Game API Settings */
        $this->apiUrl = $this->getSystemInfo('url');

        /** Extra Info */
        $this->currencyTypeInExtraInfo = $this->getSystemInfo('currencyType');
        $this->grantType = $this->getSystemInfo('grantType','operator_authorization');
        $this->scope = $this->getSystemInfo('scope','operator offline_access');
        $this->operatorName = $this->getSystemInfo('operatorName');
        $this->operatorClientId = $this->getSystemInfo('operatorClientId');
        $this->operatorSecret = $this->getSystemInfo('operatorSecret');
        $this->operatorID = $this->getSystemInfo('operatorID');
        $this->gameApiUrl = $this->getSystemInfo('gameApiUrl');
        $this->lobbyUrl = $this->getSystemInfo('lobbyUrl');
        $this->balanceUrl = $this->getSystemInfo('balanceUrl');
        $this->language = $this->getLauncherLanguage($this->getSystemInfo('language'));
        $this->hmacId = $this->getSystemInfo('hmacId');
        $this->hmacUser = $this->getSystemInfo('hmacUser');
        $this->hmacKey = $this->getSystemInfo('hmacKey');
        $this->hawkAuthentication = $this->getSystemInfo('hawkAuthentication',true);
        $this->backOfficeUrl = $this->getSystemInfo('backOfficeUrl');
        $this->pageSize = $this->getSystemInfo('pageSize',2);        
        $this->page = $this->getSystemInfo('page',1);
        $this->original_table = self::ORIGINAL_TABLE;
        //$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+1 day');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
        $this->max_retry_count = $this->getSystemInfo('sync_time_interval', 3);
        $this->force_stop_limit = $this->getSystemInfo('force_stop_limit', 1000);

        $this->token_duration         = $this->getSystemInfo('token_duration', 43200);//43200 minutes =30days
        $this->force_generate_token = $this->getSystemInfo('force_generate_token', false);

        /** Load Model Here */
        $this->loadModel($this->modelToLoad);

        $this->currentAPI = null; # default as null

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
        if(! $this->isHttpCall){
            return self::CALL_TYPE_SOAP;
        }

        return self::CALL_TYPE_HTTP;
    }

    protected function customHttpCall($ch, $params)
    {
        switch($this->method){
            case self::METHOD_POST:
                if($this->currentAPI == self::API_getTokenAndDetails){
                    $json_data = http_build_query($params);
                }else{
                    $json_data = json_encode($params);
                }
                curl_setopt($ch,CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$json_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            break;
        }
        $this->CI->utils->debug_log(__METHOD__.' REQUEST FIELD ',http_build_query($params));
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
        $apiUri = self::URI_MAP[$apiName];

        if($apiName == self::API_getGameListDetails){
            $url = rtrim($this->gameApiUrl,'/').$apiUri;
        }elseif($apiName == self::API_getRoundDetails){
            $url = rtrim($this->backOfficeUrl,'/').$apiUri;
        }elseif($apiName == self::API_getBetDetails){
            $url = rtrim($this->backOfficeUrl,'/').$apiUri;
        }elseif($apiName == self::API_syncGameRecords){
            $url = rtrim($this->backOfficeUrl,'/').$apiUri;
        }else{
            $url = rtrim($this->apiUrl,'/').$apiUri;
        }

        if($this->method == self::METHOD_GET){
            return $url.'?'.http_build_query($params);
        }

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
        if($this->currentAPI == self::API_getTokenAndDetails){
            $t =  $this->generateAuth($this->operatorClientId,$this->operatorSecret);
            $headers['Authorization'] = 'Basic '.$t;
            $headers['Content-type'] = 'application/x-www-form-urlencoded';
        }else{
            $headers['Content-Type'] = 'application/json';
        }

        if($this->currentAPI == self::API_syncGameRecords
        || $this->currentAPI == self::API_getGameListDetails 
        || $this->currentAPI == self::API_getBetDetails
        || $this->currentAPI == self::API_getRoundDetails){

            if($this->force_generate_token){
                $this->generateToken();
            }

            $clone = clone $this;
			$this->generatedToken = $clone->getAvailableApiToken();
            $headers['Authorization'] = 'Bearer '.$this->generatedToken;
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    /**
     * Process The response of Game Provider if true or false, true = success,false = error
     * 
     * @param int $responseResultId
     * @param mixed $apiResult
     * @param string $gameUsername
     * 
     * @return boolean
     */
    public function processResultBoolean($responseResultId,$apiResult,$api=null,$gameUsername = null)
    {
        $success = false;

        if($api==self::API_getTokenAndDetails){
            if(isset($apiResult['access_token'])){
                $success = true;
            }
        }

        if($api==self::API_getGameListDetails){
            $data = $this->getArrKeyVal($apiResult,'data');
            $game = $this->getArrKeyVal($data,'games');
            if(!empty($game)){
                $success = true;
            }
        }

        if($api==self::API_getRoundDetails || $api==self::API_getBetDetails || $api==self::API_syncGameRecords){
            if(isset($apiResult['data']) && is_array($apiResult['data'])){
                $success = true;
            }
        }

        if(! $success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log(__METHOD__ .' Game Got Error! =========================> ',$responseResultId,'gameUsername ',$gameUsername);
        }


        return $success;
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
       // it will create player on game_provider_auth table
       $return = parent::createPlayer($playerName,$playerId,$password,$email,$extra);
       $success = false;
       $message = "Unable to create account for LUCKY STREAK SEAMLESS";

       if($return){
          $success = true;
          $message = "Unable to create account for LUCKY STREAK SEAMLESS";
       }

       $this->CI->utils->debug_log(__METHOD__." createPlayer is:",$success);

       return [
         "success" => $success,
         "message" => $message
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
     * Get the Required token
    */
    /*
    public function getTokenAndDetails()
    {
        $this->method = self::METHOD_POST;
        $grantType = $this->grantType;
        $scope = $this->scope;
        $operatorName = $this->operatorName;
        $this->currentAPI = self::API_getTokenAndDetails;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultGetTokenAndDetails',
        ];

        $params = [
            'grant_type' => $grantType,
            'scope' => $scope,
            'operator_name' => $operatorName
        ];

        $this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>>>>>>>>>',$params);

        return $this->callApi(self::API_getTokenAndDetails,$params,$context);

    }

    public function processResultGetTokenAndDetails($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,self::API_getTokenAndDetails);

        $result = [
            'apiResult' => $arrayResult
        ];

        if($success){
            if(isset($arrayResult['access_token'])){
                $this->generatedToken = $arrayResult['access_token'];
            }
            $this->CI->utils->debug_log(__METHOD__.' token  >>>>>>>>>>>>>>>>',$arrayResult);
        }


        return [
            $success,
            $result
        ];
    }
    */

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

        if($isTokenValid){
            $gameList = $this->getGameListDetails();
            $apiResult = $this->getArrKeyVal($gameList,'apiResult');
            $data = $this->getArrKeyVal($apiResult,'data');
            $games = $this->getArrKeyVal($data,'games');

            if(! empty($games)){
                $success = false;
                $url = null;

                $params = [
                    'PlayerName' => $playerName,
                    'OperatorName' => $this->operatorName,
                    'AuthCode' => $playerCurrentToken,
                ];

                foreach($games as $value){
                    if(isset($extra['game_code'])){
                        if(isset($value['id']) && $value['id'] == $extra['game_code']){
                            $gameUrl = strtok($value['launchUrl'],'?');
                            $limitGroups = $value['limitGroups'][0]['id'];
                            $gameId = $value['id'];
                            $gameType = $value['type'];
                            $success = true;
                            break;
                        }
                        continue;
                    }else{
                        $gameUrl = strtok($games[0]['launchUrl'],'?');
                        $success = true;
                        break;
                    }
                }

                if(! empty($gameUrl)){
                    if(!empty($this->balanceUrl)){
                        $params['BalanceURL'] = $this->balanceUrl;
                    }
                    if(isset($extra['game_code'])){
                        $params['LobbyURL'] = $this->getHomeLink();
                        $params['LimitsGroupId'] = $limitGroups;
                        $params['GameId'] = $gameId;
                        $params['GameType'] = $gameType;
                        $url = $gameUrl .'?'. http_build_query($params);
                    }else{
                        $params['GameType'] = 'All';
                        $url = $gameUrl .'?'. http_build_query($params);
                    }
                }else{
                    $success = false;
                }

                $this->CI->utils->debug_log(__METHOD__.' game launch URL >>>>>>>>',$url,'params',$params);

                return [
                    'success' => $success,
                    'url' => $url
                ];
            }
        }

        return [
            'success' => false,
            'url' => null
        ];
    }

    /**
     * Get Game List Details
     * @param array $gameTypes
     * @param bollean $open
     * @param array $currencies
     */
    public function getGameListDetails($gameTypes=[],$open=true,$currencies=[])
    {
        # get token here
        /*$token = $this->getTokenAndDetails();

        if(!$token){
            $this->CI->utils->debug_log(__METHOD__.' ERROR getting token',$token);
            return [
                false,
                [
                    'token_details' => 'error'
                ]
            ];
        }*/

        $this->method = self::METHOD_POST;
        $this->currentAPI = self::API_getGameListDetails;
        $gameCurrency = $this->gameCurrency();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultGetGameListDetails',
        ];

        $params = [
            "data" => [
                "currencies" => [$gameCurrency],
                'open' => $open
            ]
        ];

        if(is_array($gameTypes) && count($gameTypes)==1){

            $params['data']['gameTypes'] = $gameTypes[0];

        }elseif(is_array($gameTypes) && count($gameTypes)>0){

            foreach($gameTypes as $val){
                $params['data']['gameTypes'][]=$val;
            }

        }

        if(is_array($currencies) && count($currencies)==1){

            $params['data']['currencies'] = $currencies[0];

        }elseif(is_array($currencies) && count($currencies)>0){

            foreach($currencies as $val){
                $params['data']['currencies'][]=$val;
            }

        }

        $this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>',$params);

        return $this->callApi(self::API_getGameListDetails,$params,$context);
    }

    public function processResultGetGameListDetails($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,self::API_getGameListDetails);


        $result = [
            'apiResult' => $arrayResult
        ];

        return [
            $success,
            $result
        ];
    }

    /** 
     * Get Round details
     * @param int|null $id The Id of the Round that you want to request
     * @param int|null $gameId The Id of the Game you want to filter
     * @param int|null $dealerId The Id of the Game you want to filter
     * @param string|null $gameType Game Type you wish to filter ("Baccarat", "Blackjack", "Roulette")
     * @param int|null $roundFrom First round Id of the range you want to filter to
     * @param int|null $roundTo Last round Id of the range you want to filter to
     * @param datetime|null $dateFrom Date from which you want to filter the Bets (ex: "2017-01-21")
     * @param datetime|null $dateTo Date till you want to filter the Bets (ex: "2017-01-22")
    */
    public function getRoundDetails($id=null,$dateFrom=null,$dateTo=null,
    $gameId=null,$dealerId=null,$gameType=null,$roundFrom=null,$roundTo=null)
    {
        # get token here
        /*$token = $this->getTokenAndDetails();

        if(!$token){
            $this->CI->utils->debug_log(__METHOD__.' ERROR getting token',$token);
            return [
                false,
                [
                    'token_details' => 'error'
                ]
            ];
        }*/

        $allParams = [
            'id' => $id,
            'gameId' => $gameId,
            'dealerId' => $dealerId,
            'gameType' => $gameType,
            'roundFrom' => $roundFrom,
            'roundTo' => $roundTo,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ];
        $this->method = self::METHOD_POST;
        $this->currentAPI = self::API_getRoundDetails;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultGetRoundDetails',
        ];

        $params = [
            'Data' => [
            ]
        ];

        foreach($allParams as $key => $value){
            if(! empty($value)){
                $params['Data'][$key] = $value;
            }
        }

        $this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>>>>>>>>>',$params);

        return $this->callApi(self::API_getRoundDetails,$params,$context);
    }

    public function processResultGetRoundDetails($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,self::API_getRoundDetails);

        $result = [
            'apiResult' => $arrayResult,
            'response_result_id' => $responseResultId
        ];

        return [
            $success,
            $result
        ];
    }

    /** 
     * Get Bet details
     * @param int|null $id The Id of the Bet that is requested from Lucky Streak base
     * @param int|null $gameId Id of the game that you request
     * @param int|null $roundId Id of the round requested
     * @param int|null $operatorId Your operator Id in Lucky Streak System
     * @param string|null $playerUserName Unique user name of the player you want to get the bets for
     * @param int|null $playerId Id of the player stored in Lucky Streak system
     * @param string|null $betStatus Status of the bet you wish to filter ("Open", "Completed", "Cancelled", "NotFunded", "FundingLost")
     * @param int|null $pageSize Number of the records returned in one page for the response (Max; 100)
     * @param int|null $page Number of the page you want to retrieve
     * @param string|null $orderBy Ordering of the response data (possible values are the once from the response parameters)
     * @param string|null $gameType Game Type you wish to filter ("Baccarat", "Blackjack", "Roulette")
     * @param datetime|null $dateFrom Date from which you want to filter the Bets (ex: "2017-01-21")
     * @param datetime|null $dateTo Date till you want to filter the Bets (ex: "2017-01-22")
    */
    public function getBetDetails($dateFrom=null,$dateTo=null,$page=null,$id=null,
    $gameId=null,$roundId=null,$operatorId=null,$playerUsername=null,$playerId=null,
    $betStatus=null,$pageSize=null,$gameType=null,$orderBy=null)
    {
        # get token here
        /*$token = $this->getTokenAndDetails();

        if(!$token){
            $this->CI->utils->debug_log(__METHOD__.' ERROR getting token',$token);
            return [
                false,
                [
                    'token_details' => 'error'
                ]
            ];
        }*/

        $allParams = [
            'id' => $id,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'gameId' => $gameId,
            'roundId' => $roundId,
            'operatorId' => $operatorId,
            'playerUsername' => $playerUsername,
            'playerId' => $playerId,
            'betStatus' => $betStatus,
            'pageSize' => $pageSize,
            'page' => $page,
            'orderBy' => $orderBy,
            'gameType' => $gameType,
        ];

        $this->method = self::METHOD_POST;
        $this->currentAPI = self::API_getBetDetails;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultGetBetDetails',
        ];

        $params = [
            'Data' => [
            ]
        ];

        foreach($allParams as $key => $value){
            if(! empty($value)){
                $params['Data'][$key] = $value;
            }
        }

        $this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>>>>>>>>>',$params);

        return $this->callApi(self::API_getBetDetails,$params,$context);
    }

    public function processResultGetBetDetails($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,self::API_getBetDetails);

        $result = [
            'apiResult' => $arrayResult,
            'response_result_id' => $responseResultId
        ];

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
    public function _syncOriginalGameLogs($token = false)
    {
        # get token here
        /*$bearertoken = $this->getTokenAndDetails();

        if(!$bearertoken){
            $this->CI->utils->debug_log(__METHOD__.' ERROR getting token',$bearertoken);
            return [
                false,
                [
                    'token_details' => 'error'
                ]
            ];
        }
        */

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $start = $startDate;
        $end = $endDate;

        $success = true;
        $step = $this->sync_time_interval;
        $rowsCount = 0;
        $retryCount = 0;


        $this->method = self::METHOD_POST;
        $this->currentAPI = self::API_syncGameRecords;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $now=new DateTime();

        if($end > $now){
           $end = $now;
        }

        while($start < $end){
            $endDate = $this->CI->utils->getNextTime($start,$step);
            $takeSleep = true;

            if($endDate > $end){
                $endDate = $end;
                $takeSleep = false;
            }

            $ds = $start->format('d');
            $de = $endDate->format('d');
            if($ds == $de){
                $endDate->modify('+1 day');
            }

            $params = [
                'Data' => [
                    'dateFrom' => $start->format('Y-m-d'),
                    'dateTo' => $endDate->format('Y-m-d'),
                    'page' => $this->page,
                    'pageSize' =>  $this->pageSize
                ]
            ];

            $this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>',$params);
            $apiResult = $this->callApi(self::API_syncGameRecords, $params, $context);
            $rowsCount += isset($apiResult['row_count']) ? $apiResult['row_count'] : 0;
            $this->CI->utils->debug_log(__METHOD__.' apiResult >>>>>>>>',$apiResult);

            # we check here if row count in API response data index is more than page limit, we do pagination here
            if(isset($apiResult["success"]) && $apiResult["success"] && isset($apiResult['is_max_return']) && $apiResult['is_max_return']){
                $this->CI->utils->info_log(__METHOD__.' details value: row_count >>>>>>>>',$apiResult['row_count'],'is_max_return',$apiResult['is_max_return'],'page',$this->page);

                $this->page++;
                sleep($this->common_wait_seconds);
                continue;
            }

            # we check if API call is not success
            if(isset($apiResult['success']) && ! $apiResult['success']){
                $retryCount++;
                if($retryCount > $this->max_retry_count){
                    $this->CI->utils->error_log(__METHOD__.' ERROR in calling API: ',$apiResult);
                    break;
                }else{
                    $this->CI->utils->debug_log(__METHOD__.' ERROR in calling API, will try to retry ',$apiResult);
                    continue;
                }
            }else{
                $start = $endDate;
            }

            if($takeSleep){
                sleep($this->common_wait_seconds);
            }
        }

        return [
            'success' => $success,
            'rows_count' => $rowsCount
        ];
    }


    /**
     * OGP-20617 Update sync original, Abstract in Parent Class
     * Sync Original Game Logs
     * 
     * @param string $token token from sync Information, found in \Game_platform_manager::class@syncGameRecordsNoMergeOnOnePlatform
     * 
     * @return
     */
    public function syncOriginalGameLogs($token = false)
    {
        # get token here
        /*$bearertoken = $this->getTokenAndDetails();

        if(!$bearertoken){
            $this->CI->utils->debug_log(__METHOD__.' ERROR getting token',$bearertoken);
            return [
                false,
                [
                    'token_details' => 'error'
                ]
            ];
        }*/

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
            $this->maxPage = $currentPage = self::START_PAGE;

            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }
            
            while (!$done) {
                $this->utils->debug_log("############# LUCKY STREAK: (syncOriginalGameLogs) queryDateTimeStart:",$queryDateTimeStart," queryDateTimeEnd: ", $queryDateTimeEnd, 'currentPage',$currentPage);                
                $params = [
                    'Data' => [
                        'dateFrom' => $startDateParam->format('Y-m-d H:i:s'),
                        'dateTo' => $endDateParam->format('Y-m-d H:i:s'),
                        'page' => $currentPage,
                        'pageSize' =>  $this->pageSize
                    ]
                ];
                $rlt = $this->callApi(self::API_syncGameRecords, $params, $context);
                $this->utils->debug_log("############# LUCKY STREAK: (syncOriginalGameLogs) rlt:",$rlt);
                
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
        $success = $this->processResultBoolean($responseResultId, $arrayResult, self::API_syncGameRecords);
        $gameRecords = !empty($arrayResult['data']) ? $arrayResult['data']:[];
        $this->utils->debug_log("############# LUCKY STREAK: (syncOriginalGameLogs) arrayResult:",$arrayResult);
        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false,
            'row_count' => 0
        );

        if($success && !empty($gameRecords)) {

            $totalRows = isset($arrayResult['pageInfo']['totalItems']) ? $arrayResult['pageInfo']['totalItems'] : 0;
            $pageSize = isset($arrayResult['pageInfo']['pageSize']) ? $arrayResult['pageInfo']['pageSize'] : 0;
            $currentPage = isset($arrayResult['pageInfo']['currentPage']) ? $arrayResult['pageInfo']['currentPage'] : 0;
            $this->maxPage = $totalPages = isset($arrayResult['pageInfo']['totalPages']) ? $arrayResult['pageInfo']['totalPages'] : 0;
            $this->CI->utils->info_log(__METHOD__.' totalItems >>>>>>>',$totalRows);
            
            # check if data is more than page limit
            if($currentPage*$pageSize < $totalRows){

                $dataResult = $this->insertOgl($gameRecords,$responseResultId);
                $this->CI->utils->debug_log(__METHOD__.' data result >>>>>>>>',$dataResult);

                return [
                    true,
                    [
                        'is_max_return' => true,
                        'row_count' => $totalRows
                    ]
                ];
            }
            $dataResult['is_max_return'] = false;
            $dataResult['row_count'] = $totalRows;
            $this->rowsCount+= $totalRows;
            $dataResult = $this->insertOgl($gameRecords,$responseResultId);
            $this->CI->utils->debug_log(__METHOD__.' data result >>>>>>>>',$dataResult);

        }
        return array($success, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            $elapsed=intval($this->utils->getExecutionTimeToNow()*1000);
            foreach($gameRecords as $index => $record) {
                $data['betId'] = isset($record['betId']) ? $record['betId'] : null;
                $data['playerId'] = isset($record['playerId']) ? $record['playerId'] : null;
                $data['operatorId'] = isset($record['operatorId']) ? $record['operatorId'] : null;
                $data['playername'] = isset($record['playerName']) ? $this->getGameUsernameByPlayerUsername($record['playerName']) : null;
                $data['roundId'] = isset($record['roundId']) ? $record['roundId'] : null;
                $data['gameId'] = isset($record['gameId']) ? $record['gameId'] : null;
                $data['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
                $data['gameType'] = isset($record['gameType']) ? $record['gameType'] : null;
                $data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
                $data['winAmount'] = isset($record['winAmount']) ? $record['winAmount'] : null;
                $data['income'] = isset($record['income']) ? $record['income'] : null;
                $data['betTime'] = isset($record['placingTime']) ? $this->gameTimeToServerTime($record['placingTime']) : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['sessionId'] = isset($record['sessionId']) ? $record['sessionId'] : null;
                $data['extra_info'] = is_array($record) ? json_encode($record) : null;
                $data['external_unique_id'] = (isset($record['betId']) && isset($record['roundId'])) ? ($record['betId'].'-'.$record['roundId']) : null;
                $data['response_result_id'] = $responseResultId;
                $data['elapsed_time'] = $elapsed;
                $gameRecords[$index] = $data;
                unset($data);

            }
        }
    }

    /**
     * Insert Original Game logs daata
     * 
     * @param array $gameRecords
     * @param int $responseResultId
     * 
     * @return array
     * 
     */
    public function insertOgl(&$gameRecords,$responseResultId)
    {

        $this->processGameRecords($gameRecords,$responseResultId);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            $this->original_table,
            $gameRecords,
            'external_unique_id',
            'external_unique_id',
            self::MD5_FIELDS_FOR_ORIGINAL,
            'md5_sum',
            'id',
            self::MD5_FLOAT_AMOUNT_FIELDS
        );

        $dataResult['data_count'] = is_array($gameRecords) ? count($gameRecords) : null;

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] = $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] = $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
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
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $table = $this->original_table;
        $sqlTime='lucky.betTime >= ? and lucky.betTime <= ?';
        $sql = <<<EOD
SELECT
    lucky.id as sync_index,
    lucky.roundId,
    lucky.gameId as game_code,
    lucky.status,
    lucky.betAmount as bet_amount,
    lucky.winAmount,
    lucky.income as result_amount,
    lucky.betTime,

    lucky.external_unique_id as external_uniqueid,
    lucky.updated_at,
    lucky.md5_sum,
    lucky.response_result_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as lucky
    LEFT JOIN game_description ON lucky.gameId = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON lucky.playername = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

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
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null # no after balance in API response
            ],
            'date_info' => [
                'start_at' => $row['betTime'],
                'end_at' => $row['betTime'],
                'bet_at' => $row['betTime'],
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['roundId'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null # BET_TYPE_MULTI_BET or BET_TYPE_SINGLE_BET
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            // from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null     
        ];
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        switch($row['status']) {
            case 1:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }
        $roundId = isset($row['roundId']) ? $row['roundId'] : null;
        $row['bet_details'] = $this->generateBetDetails($roundId);
    }

    public function generateBetDetails($id)
    {
        $r = [];
        if(! empty($id)){
            $result = $this->getRoundDetails($id);

            if(isset($result['success']) && $result['success']){
                $aR = isset($result['apiResult']['data'][0]) ? $result['apiResult']['data'][0] : null;
    
                return [
                    'round_id' => isset($aR['roundId']) ? $aR['roundId']: null,
                    'game' => isset($aR['game']) ? $aR['game']: null,
                    'dealer' => isset($aR['dealer']) ? $aR['dealer']: null,
                    'round_status' => isset($aR['roundStatusFrm']) ? $aR['roundStatusFrm']: null,
                    'round_result' => isset($aR['roundResult']) ? $aR['roundResult']: null,
                ];
            }
        }

        return $r;
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
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function getLauncherLanguage($currentLang) 
    {
        $this->CI->load->library(array('language_function'));
       switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi":
                $language = 'vi';
                break;
            case "en":
                $language = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
                $language = 'th';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    /**
     * Generate Basic Autorization text
     * 
     * @return mixed
     */
    public function generateAuth($clientId,$operatorSecret)
    {
        $t = base64_encode($clientId.':'.$operatorSecret);
        if(!empty($t)){
            return $t;
        }
        return null;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
       $this->CI->load->model(array('original_game_logs_model'));
           
$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.start_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.transaction_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;   
EOD;
   
   $params=[$this->getPlatformCode(),$startDate, $endDate];
   
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
   
    /*public function processTransactions(&$transactions){
        $temp_game_records = [];
      
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);             
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];
   
                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], ['bet'])){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }
   
        $transactions = $temp_game_records;
    }*/

	/**
	 * will check timeout, if timeout then call again
	 * @return token
	 */
    public function getAvailableApiToken(){
        return $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
    }

    /**
     *
     * Login And Get JWT Token (登入並取得 JWT 令牌)
     *
     * @return      array
     *
     */
	public function generateToken(){
        $this->method = self::METHOD_POST;
        $grantType = $this->grantType;
        $scope = $this->scope;
        $operatorName = $this->operatorName;
        $this->currentAPI = self::API_getTokenAndDetails;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken',
        ];

        $params = [
            'grant_type' => $grantType,
            'scope' => $scope,
            'operator_name' => $operatorName
        ];

        $this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>>>>>>>>>',$params);

        return $this->callApi(self::API_getTokenAndDetails,$params,$context);
	}

	public function processResultForGenerateToken($params){
		$this->CI->utils->debug_log('LUCKYSTREAK (processResultForGenerateToken)', $params);	
        
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,self::API_getTokenAndDetails);

        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

		if($success){
			$api_token = @$arrayResult['access_token'];			
			$token_timeout = new DateTime($this->utils->getNowForMysql());
			$token_timeout->modify("+{$this->token_duration} minutes");
			$result['api_token']=$api_token;
			$result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
        }
        
		$this->CI->utils->debug_log('LUCKYSTREAK (processResultForGenerateToken) result:', $result);	
		return array($success,$result);
	}

}//end of class