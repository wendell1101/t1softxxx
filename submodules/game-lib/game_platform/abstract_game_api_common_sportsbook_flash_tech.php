<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Ticket Number: OGP-18708
 * Wallet Type(Transfer/Seamless) : Transfer
 *
 * @see Service Interface Description Version:1.8.1.7
 *
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Abstract_game_api_common_sportsbook_flash_tech extends Abstract_game_api
 {
    public $partnerKey;
    public $memberSetting;

    /** default original game logs table @var const*/
    const OGL = 'flash_tech_game_logs';

    /** callback endpoints in our side @var const */
    const LOGIN_AUTHENTICATE = 'login_authenticate';

    /** game view restricted in mobile @var const */
    const GAME_VIEW_RESTRICTED_IN_MOBILE = 'v2';

    /** default in game view @var const */
    const GAME_VIEW_DEFAULT = 'v1';

    /** callback success code @var const */
    const CALLBACK_SUCCESS_CODE = 0;

    /** success code @var const */
    const CODE_SUCCESS = 0;

    /** UserAlreadyExists @var const */
    const CODE_USER_ALREADY_EXISTS = -98;

    /** codes in transaction checking @var const */
    const CODE_TRANSACTION_APPLY = 0;
    const CODE_TRANSACTION_SUCCESS = 1;
    const CODE_TRANSACTION_FAILED = 2;

    const CALLBACK_ENDPOINTS = [
        self::LOGIN_AUTHENTICATE
    ];

    /** methods of game provider API  @var const */
    const METHOD_CREATE_MEMBER = 'createmember';
    const METHOD_GET_BALANCE = 'getbalance';
    const METHOD_BALANCE_TRANSFER = 'balancetransfer';
    const METHOD_IS_PLAYER_ONLINE = 'isonline';
    const METHOD_IS_PLAYER_EXISTS = 'exist';
    const METHOD_QUERY_TRANSACTION = 'checkfundtransferstatus';
    const METHOD_QUERY_BET_RECORD = 'betrecord';
    const METHOD_LANGUAGE_INFO = 'languageinfo';
    const METHOD_PARLAY_BET_RECORD = 'parlaybetrecord';
    const METHOD_UPDATE_USER_SETTING = 'updatemembersetting';
    const METHOD_GET_USER_DEFAULT_BET_LIMIT = 'getmembersetting';

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    /** payment type of game provider API  @var const */
    const PAYMENT_TYPE_DEPOSIT = 1;
    const PAYMENT_TYPE_WITHDRAW = 0;

    /** game status code  @var const */
    const GAME_STATUS_PENDING = "P";
    const GAME_STATUS_WIN_ALL = "WA";
    const GAME_STATUS_WIN_HALF = "WH";
    const GAME_STATUS_LOSE_ALL = "LA";
    const GAME_STATUS_LOSE_HALF = "LH";
    const GAME_STATUS_DRAW = "D";

    const WIN_LOST_STATUS_MAP = [
        self::GAME_STATUS_WIN_ALL => 'Win All',
        self::GAME_STATUS_WIN_HALF => 'Win Half',
        self::GAME_STATUS_LOSE_ALL => 'Lose All',
        self::GAME_STATUS_LOSE_HALF => 'Lose Half',
        self::GAME_STATUS_DRAW => 'Draw',
        self::GAME_STATUS_PENDING => 'Pending',
    ];

    const BET_STATUS_HANDLING = 'D';
    const BET_STATUS_ACCEPTED = 'A';
    const BET_STATUS_CANCELLED = 'C';
    const BET_STATUS_REJECTED = 'R';

    const LANGUAGE_TYPE_TEAM = 0;
    const LANGUAGE_TYPE_LEAGUE = 1;
    const LANGUAGE_TYPE_SPECIAL_BET_NAME = 2;

    const API_queryLanguageInfo = 'queryLanguageInfo';
    const API_queryParlayRecord = 'queryParlayRecord';

    /**
     * BET Type
     *
     * @var const BET_TYPES
     */
    const BET_TYPES = [
        '1' => 'Bet 1 in 1X2',
        '2' => 'Bet 2 in 1X2',
        'CS' => 'Correct Score',
        'FLG' => 'First Goal / Last Goal',
        'HDP' => 'Handicap',
        'HFT' => 'Half Time / Full Time',
        'OE' => 'Odd / Even',
        'OU' => 'Over / Under',
        'OUT' => 'Outright',
        'PAR' => 'Parlay',
        'TG' => 'Total Goal',
        'X' => 'Bet X in 1X2',
        '1X' => 'Bet 1X in Double Chance(DC)',
        '12' => 'Bet 12 in Double Chance(DC)',
        'X2' => 'Bet X2 in Double Chance(DC)'
    ];

    /**
     * Odds Type
     *
     * @var const ODDS_TYPE
     */
    const ODDS_TYPE = [
        'MY' => 'Malay',
        'ID' => 'Indo',
        'HK' => 'Hong Kong',
        'DE' => 'Decimal',
        'MR' => 'Myanmar'
    ];

    protected $method;
    /**
     * URI MAP of Game API Endpoints
     *
     * @var const URI_MAP
     */
    const URI_MAP = [
        self::API_createPlayer => 'SportsApi.aspx',
        self::API_depositToGame => 'SportsApi.aspx',
        self::API_withdrawFromGame => 'SportsApi.aspx',
        self::API_queryPlayerBalance => 'SportsApi.aspx',
        self::API_queryForwardGame => 'auth.aspx',
        self::API_isPlayerOnline=> 'SportsApi.aspx',
        self::API_isPlayerExist => 'SportsApi.aspx',
        self::API_queryTransaction => 'SportsApi.aspx',
        self::API_syncGameRecords => 'SportsApi.aspx',
        self::API_queryLanguageInfo => 'SportsApi.aspx',
        self::API_queryParlayRecord => 'SportsApi.aspx',
        self::API_setMemberBetSetting => 'SportsApi.aspx',
        self::API_getMemberBetSetting => 'SportsApi.aspx',
    ];

    /**
     * Fields in table, we want to detect changes for update in fields
     * @var constant MD5_FIELDS_FOR_ORIGINAL
    */
    const MD5_FIELDS_FOR_ORIGINAL = [
        'versionId',
        'SourceName',
        'ReferenceNo',
        'SocTransId',
        'IsFirstHalf',
        'TransDate',
        'IsHomeGive',
        'IsBetHome',
        'BetAmount',
        'Outstanding',
        'Hdp',
        'Odds',
        'Currency',
        'WinAmount',
        'ExchangeRate',
        'WinLoseStatus',
        'TransType',
        'DangerStatus',
        'MemCommission',
        'BetIp',
        'HomeScore',
        'AwayScore',
        'RunHomeScore',
        'RunAwayScore',
        'IsRunning',
        'RejectReason',
        'SportType',
        'Choice',
        'WorkingDate',
        'OddsType',
        'MatchDate',
        'HomeTeamId',
        'AwayTeamId',
        'LeagueId',
        'SpecialId',
        'StatusChange',
        'StateUpdateTs',
        'MemCommissionSet',
        'IsCashOut',
        'CashOutTotal',
        'CashOutTakeBack',
        'CashOutWinLoseAmount',
        'BetSource',
        'AOSExcluding',
        'MMRPercent',
        'MatchID',
        'MatchGroupID',
        'BetRemarks',
        'IsSpecial'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'BetAmount',
        'Outstanding',
        'Hdp',
        'Odds',
        'WinAmount',
        'ExchangeRate',
        'MemCommission',
        'MemCommissionSet',
        'CashOutTotal',
        'CashOutTakeBack',
        'CashOutWinLoseAmount',
        'MMRPercent',
        'StateUpdateTs'
    ];

    # Fields in game_logs table, we want to detect changes for merge, and when .md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'game_code',
        'ReferenceNo',
        'BetAmount',
        'WinAmount',
        'TransDate',
        'MatchDate',
        'WinLoseStatus',
        'DangerStatus',
        'Odds',
        'Transtype',
        'OddsType'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'BetAmount',
        'WinAmount'
    ];

    public function __construct()
    {
        parent::__construct();

        /** Game API settings */
        $this->apiUrl = $this->getSystemInfo('apiUrl','http://api.1win888.net');
        $this->gameLaunchWebUrl = $this->getSystemInfo('gameLaunchWebUrl','http://rpthb.1win888.net');
        $this->gameLaunchMobileUrl = $this->getSystemInfo('gameLaunchMobileUrl','http://rpthbmobile.1win888.net');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

        # fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 20);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 8);

        /** extra info */
        $this->originalGameLogsTable = $this->getSystemInfo('originalGameLogsTable',self::OGL);
        $this->partnerKey = $this->getSystemInfo('partnerKey',3266354965567627);
        $this->gameLanguage = $this->getSystemInfo('gameLanguage');
        $this->gameTemplatename = $this->getSystemInfo('gameTemplatename','aliceblue');
        $this->gameView = $this->getSystemInfo('gameView','v1');

        /** Game API config */
        $this->noLoginEntry = $this->getSystemInfo('noLoginEntry',false);
        $this->sleepTime = $this->getSystemInfo('sleepTime',11);
        $this->always_ovewrite_cache = $this->getSystemInfo('always_ovewrite_cache',false);
        $this->game_records_precision = $this->getSystemInfo('game_records_precision', 3);
        $this->memberSetting = $this->getSystemInfo('memberSetting');
        /*
        ex json of member setting on extra info
        memberSetting
        {"GroupType":"A","CashOut":false,"CommSettings":[{"CommType":"Comm","Comm":0},{"CommType":"CommOBT","Comm":0},{"CommType":"Comm1X2","Comm":0},{"CommType":"CommPAR","Comm":0},{"CommType":"CommMMR","Comm":0}],"BetSettings":[{"MaxBetLimit":100,"MaxBetMatch":101,"MinBetLimit":10,"SportType":"S"},{"MaxBetLimit":100,"MaxBetMatch":101,"MinBetLimit":10,"SportType":"PAR"},{"MaxBetLimit":100,"MaxBetMatch":101,"MinBetLimit":10,"SportType":"OT"}]}
        */

        /** other property */
    }

    /**
     * Get Platform code of Game API
     *
     * @return int game platform code
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
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        # it will create record in db, but if already exists, nothing happen
        // parent::createPlayer($playerName,$playerId,$password,$email,$extra);
        // $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId
        ];

        $params = [
            'Method' => self::METHOD_CREATE_MEMBER,
            'PartnerKey' => $this->partnerKey,
            'UserName' => $gameUsername,
            'Currency' => $this->currency
        ];

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function createPlayerInDB($playerName, $playerId, $password, $email = null, $extra = null) {

        //write to db
        $this->CI->load->model(array('game_provider_auth', 'player_model', 'agency_model'));

        $row = $this->CI->game_provider_auth->getByPlayerIdGamePlatformId($playerId, $this->getPlatformCode());

        if (empty($row)) {
            //convert username, not right name
            // $playerName = $this->convertUsernameToGame($playerName);

            $source = Game_provider_auth::SOURCE_REGISTER;
            if ($extra && array_key_exists('source', $extra) && $extra['source']) {
                $source = $extra['source'];
            }

            $is_demo_flag = false;
            if ($extra && array_key_exists('is_demo_flag', $extra) && $extra['is_demo_flag']) {
                $is_demo_flag = $extra['is_demo_flag'];
            }

            $player = (array) $this->CI->player_model->getPlayerById($playerId);

            $result = $this->CI->game_provider_auth->savePasswordForPlayerWithProcessedLoginName(
                array(
                    'username' => $playerName,
                    "id" => $playerId,
                    "password" => $password,
                    "source" => $source,
                    "is_demo_flag" => $is_demo_flag,
                    "agent_id" => @$player['agent_id'],
                    "sma_id" => (array_key_exists("root_agent_id",$player)) ? $player['root_agent_id'] : NULL
                ),
                $this->getPlatformCode(), $extra);

        } else if (!empty($extra['fix_username_limit'])
            && $extra['fix_username_limit']
            && !$this->CI->game_provider_auth->loginNameIsRandomlyGenerated($row, $playerName, $this->getSystemInfo('prefix_for_username'))
            && !$this->CI->game_provider_auth->isRegisterd($playerId, $this->getPlatformCode())
            ){

            $player = (array) $this->CI->player_model->getPlayerById($playerId);

            $result = $this->CI->game_provider_auth->savePasswordForPlayerWithProcessedLoginName(
                array(
                    'username' => $playerName,
                    "id" => $playerId,
                    "password" => $password,
                    "source" => $row['source'],
                    "is_demo_flag" => $row['is_demo_flag'],
                    "agent_id" => @$player['agent_id'],
                    "sma_id" => (array_key_exists("root_agent_id",$player)) ? $player['root_agent_id'] : NULL
                ),
                $this->getPlatformCode(), $extra);

        } else if( !empty($extra['fix_username_limit'])
            && $extra['fix_username_limit']
            && !$this->CI->game_provider_auth->isRegisterd($playerId, $this->getPlatformCode())
            && !$this->CI->game_provider_auth->loginNameIsCorrectLength($row['login_name'], $extra)
        ){
            $player = (array) $this->CI->player_model->getPlayerById($playerId);

            $result = $this->CI->game_provider_auth->savePasswordForPlayerWithProcessedLoginName(
                array(
                    'username' => $playerName,
                    "id" => $playerId,
                    "password" => $password,
                    "source" => $row['source'],
                    "is_demo_flag" => $row['is_demo_flag'],
                    "agent_id" => @$player['agent_id'],
                    "sma_id" => (array_key_exists("root_agent_id",$player)) ? $player['root_agent_id'] : NULL
                ),
                $this->getPlatformCode(), $extra);

        }else {
            $result = true;
        }


        return $result;
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName,self::API_createPlayer);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['player'] = $playerName;
        }

        return [$success, $result];
    }

    /**
     * Deposit To Game
     *
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     *
     * @return
     */
    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        ];

        $params = [
            'Method' => self::METHOD_BALANCE_TRANSFER,
            'PartnerKey' => $this->partnerKey,
            'UserName' => $gameUsername,
            'PaymentType' => self::PAYMENT_TYPE_DEPOSIT,
            'Money' => $this->gameAmountToDB($amount),
            'TicketNo' => $external_transaction_id
        ];

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    /**
     * Process Deposit
     * @param $params
     * @return array
     */
    public function processResultForDepositToGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName,self::API_depositToGame);
        $statusCode = $this->getStatusCodeFromParams($params);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        $code=isset($resultArr['Code']) ? $resultArr['Code'] : null;

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id'] = $this->getReasons($code);
            }
        }            

        if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){

            //treat as success on error 500
            $this->CI->utils->debug_log('processResultForDepositToGame', 'treat_500_as_success_on_deposit', $treat_500_as_success_on_deposit, 'statusCode', $statusCode);
            $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $success=true;
        }

        return [$success, $result];
    }

    /**
     * Withdraw From Game
     *
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     *
     * @return
     */
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        ];

        $params = [
            'Method' => self::METHOD_BALANCE_TRANSFER,
            'PartnerKey' => $this->partnerKey,
            'UserName' => $gameUsername,
            'PaymentType' => self::PAYMENT_TYPE_WITHDRAW,
            'Money' => $this->gameAmountToDB($amount),
            'TicketNo' => $external_transaction_id
        ];

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    /**
     * Process Withdraw
     * @param $params
     * @return array
     */
    public function processResultForWithdrawFromGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName,self::API_withdrawFromGame);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        $code=isset($resultArr['Code']) ? $resultArr['Code'] : null;

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($code);
        }

        return [$success, $result];
    }

    /**
     * Query Player Balance thru Game Provider API
     *
     * @param string $playerName
     *
     * @return
    */
    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        ];

        $params = [
            'Method' => self::METHOD_GET_BALANCE,
            'PartnerKey' =>  $this->partnerKey,
            'UserName' => $gameUsername
        ];

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    /**
     * Process Query Player Balance
     */
    public function processResultForQueryPlayerBalance($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName,self::API_queryPlayerBalance);

        $result = ['response_result_id'=>$responseResultId];

        if($success){
            if(isset($resultArr['Data'][0]['BetAmount'])){
                $result['balance'] = $this->gameAmountToDB($resultArr['Data'][0]['BetAmount']);
            }else{
                //wrong result, call failed
                $success=false;
            }
        }

        return [$success, $result];
    }

    /**
     * Query Transaction of Player thru Game Provider API
     *
     * @param string $playerName
     * @param array $extra
     *
     * @return
    */
    public function queryTransaction($transactionId, $extra)
    {
        $playerId=$extra['playerId'];
        $playerName=$extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'external_transaction_id' => $transactionId,
        ];

        $params = [
            'Method' => self::METHOD_QUERY_TRANSACTION,
            'PartnerKey' =>  $this->partnerKey,
            'UserName' => $gameUsername,
            'TicketNo' => $transactionId
        ];

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName,self::API_queryTransaction);

        $result=[
            'response_result_id'=>$responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        //always set reason id if could
        if(isset($resultArr['Code'])){
            $result['reason_id'] = $this->getReasons($resultArr['Code']);
        }

        if($success){
            if(isset($resultArr['Data']) && is_array($resultArr['Data']) && !empty($resultArr['Data'])){

                # check if transaction is found
                if(isset($resultArr['Data'][0]['TicketNo']) && $resultArr['Data'][0]['TicketNo'] == $external_transaction_id){
                    # check the status of transaction
                    if(isset($resultArr['Data'][0]['Status'])){

                        $status = $resultArr['Data'][0]['Status'];

                        if($status ==   self::CODE_TRANSACTION_SUCCESS){
                            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                        }elseif($status ==   self::CODE_TRANSACTION_FAILED){
                            $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
                        }elseif($status ==   self::CODE_TRANSACTION_APPLY){
                            $result['status']=self::COMMON_TRANSACTION_STATUS_PROCESSING;
                        }
                    }
                }else{
                    //if not found , still keep unknown, but it's not normal
                    $this->CI->utils->debug_log('can not find transaction on result', $external_transaction_id, $resultArr);
                }
            }
        }else{
            if($result['reason_id'] != self::REASON_UNKNOWN){
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
             }
             $this->CI->utils->debug_log(__METHOD__.' ERROR in processResultForQueryTransaction with external_transaction_id of >>>>>>>>>>>>>',$external_transaction_id,'arrayResult: ',$resultArr);
        }

        $this->CI->utils->debug_log(__METHOD__.' processResultForQueryTransaction >>>>>>>>>>>>>',$resultArr);

        return [$success, $result];
    }

    /**
     * Game Launching
     *
     * @param string $playerName
     * @param array $extra
     *
     * @return
     */
    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $activeToken = $this->getPlayerTokenByUsername($playerName);
        $lang = isset($extra['language']) ? $extra['language'] : null;
        $gameUrl = $this->gameLaunchWebUrl;
        $success = false;
        $isMobile = false;

        if(isset($extra['is_mobile']) && $extra['is_mobile']){
            $isMobile = true;
            $gameUrl = $this->gameLaunchMobileUrl;
        }

        if($this->isTokenValid($activeToken)){
            $success = true;



            if(!  $this->noLoginEntry){
                $params = [
                    'lang' => $this->getLauncherLanguage($lang),
                    'user' => $gameUsername,
                    'token' => $activeToken,
                    'currency' => $this->currency,
                    'templatename' => $this->gameTemplatename,
                    'view' => $this->gameView
                ];

                if($isMobile && $this->gameView == self::GAME_VIEW_RESTRICTED_IN_MOBILE){
                    $params['view'] = self::GAME_VIEW_DEFAULT;
                }
            }else{
                $params = [
                    'lang' => $this->getLauncherLanguage($lang),
                    'templatename' => $this->gameTemplatename
                ];
            }

            if(! empty($this->gameLanguage)){
                $params['lang'] = $this->gameLanguage;
            }

            $urlParams = http_build_query($params);

            $url = rtrim($gameUrl,'/').'/'.self::URI_MAP[self::API_queryForwardGame].'?'.$urlParams;

            $this->CI->utils->debug_log(__METHOD__.' Game URL and params >>>>>>>>',$params);

            return [
                'url' => $url,
                'success' => $success
            ];

        }else{

            $this->CI->utils->debug_log(__METHOD__.' Error in token >>>>>>>>',$activeToken);

            return [
                'url' => null,
                'success' => $success
            ];
        }
    }

    /**
     * Get Proper Game Language
     * @param mixed $currentLang
     * @return int
    */
    public function getLauncherLanguage($currentLang)
    {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = "zh-CN";
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = "id-ID";
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = "vi-VN";
                break;
            case "en-us":
                $language = "en-US";
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-th":
                $language = "th-TH";
                break;
            default:
                $language = "en-US";
                break;
        }
        return $language;
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
        $last_sync_id = $this->getLastSyncIdFromTokenOrDB($token);

        $sync_id = !empty($last_sync_id) ? $last_sync_id : 0;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        ];

        $params = [
            'Method' => self::METHOD_QUERY_BET_RECORD,
            'PartnerKey' => $this->partnerKey,
            'Version' => $sync_id
        ];

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    /**
     * Process Data
     *
     * @param array $data
     * @param int $responseResultId
     * @return array
     */
    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model(['original_game_logs_model']);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult,null,self::API_syncGameRecords);
        $gameRecords = (isset($arrayResult['Data']) && is_array($arrayResult['Data'])) ?
            $arrayResult['Data'] : [];

        $dataResult = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
        ];

        if($success && count($gameRecords) > 0){

            $this->processGameRecords($gameRecords, $responseResultId);

            $lastRecord = array_values(array_slice($gameRecords, -1))[0];

            if(array_key_exists('versionId', $lastRecord) && !empty($lastRecord['versionId'])){
                $result['last_sync_id'] = $lastRecord['versionId'];
                $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $lastRecord['versionId']);
            }else{
                $this->CI->utils->error_log(__METHOD__." Error: Last sync index not updated");
            }

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalGameLogsTable,
                $gameRecords,
                'external_unique_id',
                'external_unique_id',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log(__METHOD__.' after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            $dataResult['data_count'] = count($gameRecords);
			if (!empty($insertRows)) {
				$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
        }

        $this->CI->utils->info_log(__METHOD__.' sleeping in seconds: ',$this->sleepTime);
        sleep($this->sleepTime);
        $this->CI->db->_reset_select();
        $this->CI->db->reconnect();
        $this->CI->db->initialize();

        return [$success,$dataResult];
    }

    public function processGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords)){
            $elapsed=intval($this->CI->utils->getExecutionTimeToNow()*1000);
            foreach($gameRecords as $index => $record){
                $data['versionId'] = isset($record['Id']) ? $record['Id'] : null;
                $data['SourceName'] = isset($record['SourceName']) ? $record['SourceName'] : null;
                $data['ReferenceNo'] = isset($record['ReferenceNo']) ? $record['ReferenceNo'] : null;
                $data['ReferenceNo'] = isset($record['ReferenceNo']) ? $record['ReferenceNo'] : null;
                $data['SocTransId'] = isset($record['SocTransId']) ? $record['SocTransId'] : null;
                $data['IsFirstHalf'] = isset($record['IsFirstHalf']) ? $record['IsFirstHalf'] : null;
                $data['TransDate'] = isset($record['TransDate']) ? $this->convertTickIntoDateTime($record['TransDate']) : null;
                $data['IsHomeGive'] = isset($record['IsHomeGive']) ? $record['IsHomeGive'] : null;
                $data['IsBetHome'] = isset($record['IsBetHome']) ? $record['IsBetHome'] : null;
                $data['BetAmount'] = isset($record['BetAmount']) ? $this->gameAmountToDBTruncateNumber($record['BetAmount'], $this->game_records_precision) : null;
                $data['Outstanding'] = isset($record['Outstanding']) ? $this->gameAmountToDBTruncateNumber($record['Outstanding'], $this->game_records_precision) : null;
                $data['Hdp'] = isset($record['Hdp']) ? $record['Hdp'] : null;
                $data['Odds'] = isset($record['Odds']) ? $record['Odds'] : null;
                $data['Currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['WinAmount'] = isset($record['WinAmount']) ? $this->gameAmountToDBTruncateNumber($record['WinAmount'], $this->game_records_precision) : null;
                $data['ExchangeRate'] = isset($record['ExchangeRate']) ? $record['ExchangeRate'] : null;
                $data['WinLoseStatus'] = isset($record['WinLoseStatus']) ? $record['WinLoseStatus'] : null;
                $data['TransType'] = isset($record['TransType']) ? $record['TransType'] : null;
                $data['DangerStatus'] = isset($record['DangerStatus']) ? $record['DangerStatus'] : null;
                $data['MemCommission'] = isset($record['MemCommission']) ? $record['MemCommission'] : null;
                $data['BetIp'] = isset($record['BetIp']) ? $record['BetIp'] : null;
                $data['HomeScore'] = isset($record['HomeScore']) ? $record['HomeScore'] : null;
                $data['AwayScore'] = isset($record['AwayScore']) ? $record['AwayScore'] : null;
                $data['RunHomeScore'] = isset($record['RunHomeScore']) ? $record['RunHomeScore'] : null;
                $data['RunAwayScore'] = isset($record['RunAwayScore']) ? $record['RunAwayScore'] : null;
                $data['IsRunning'] = isset($record['IsRunning']) ? $record['IsRunning'] : null;
                $data['RejectReason'] = isset($record['RejectReason']) ? $record['RejectReason'] : null;
                $data['SportType'] = isset($record['SportType']) ? $record['SportType'] : null;
                $data['Choice'] = isset($record['Choice']) ? $record['Choice'] : null;
                $data['WorkingDate'] = isset($record['WorkingDate']) ? $record['WorkingDate'] : null;
                $data['OddsType'] = isset($record['OddsType']) ? $record['OddsType'] : null;
                $data['MatchDate'] = isset($record['MatchDate']) ?  $this->convertTickIntoDateTime($record['MatchDate']) : null;
                $data['HomeTeamId'] = isset($record['HomeTeamId']) ? $record['HomeTeamId'] : null;
                $data['AwayTeamId'] = isset($record['AwayTeamId']) ? $record['AwayTeamId'] : null;
                $data['LeagueId'] = isset($record['LeagueId']) ? $record['LeagueId'] : null;
                $data['SpecialId'] = isset($record['SpecialId']) ? $record['SpecialId'] : null;
                $data['StatusChange'] = isset($record['StatusChange']) ? $record['StatusChange'] : null;
                $data['StateUpdateTs'] = isset($record['StateUpdateTs']) ? $this->convertTickIntoDateTime($record['StateUpdateTs']) : null;
                $data['MemCommissionSet'] = isset($record['MemCommissionSet']) ? $record['MemCommissionSet'] : null;
                $data['IsCashOut'] = isset($record['IsCashOut']) ? $record['IsCashOut'] : null;
                $data['CashOutTotal'] = isset($record['CashOutTotal']) ? $record['CashOutTotal'] : null;
                $data['CashOutTakeBack'] = isset($record['CashOutTakeBack']) ? $record['CashOutTakeBack'] : null;
                $data['CashOutWinLoseAmount'] = isset($record['CashOutWinLoseAmount']) ? $record['CashOutWinLoseAmount'] : null;
                $data['BetSource'] = isset($record['BetSource']) ? $record['BetSource'] : null;
                $data['AOSExcluding'] = isset($record['AOSExcluding']) ? $record['AOSExcluding'] : null;
                $data['MMRPercent'] = isset($record['MMRPercent']) ? $record['MMRPercent'] : null;
                $data['MatchID'] = isset($record['MatchID']) ? $record['MatchID'] : null;
                $data['MatchGroupID'] = isset($record['MatchGroupID']) ? $record['MatchGroupID'] : null;
                $data['BetRemarks'] = isset($record['BetRemarks']) ? $record['BetRemarks'] : null;
                $data['IsSpecial'] = isset($record['IsSpecial']) ? $record['IsSpecial'] : null;

                //$data['extra_info'] = $this->generateExtraInfoInOgl($record,$responseResultId);
                $data['elapsed_time'] = $elapsed;

                # default data
                $data['external_unique_id'] = $data['MatchID'].'-'.$data['ReferenceNo'];

                //extrainfo
                $_record = $record;
                $_record['response_result_id'] = $responseResultId;
                $_record['home_team_name'] = $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_TEAM,$data['HomeTeamId']);
                $_record['away_team_name'] = $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_TEAM,$data['AwayTeamId']);
                $_record['leaque_name'] = $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_LEAGUE,$data['LeagueId']);
                $_record['parlay_data'] = [];
                $data['extra_info'] = json_encode($_record);
                if($data['TransType']=='PAR'){
                    $parlayData = $this->queryParlayRecord($_record);
                    $_record['parlay_data'] = isset($parlayData['data'])?$parlayData['data']:[];
                    $data['extra_info'] = json_encode($_record);
                }

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalGameLogsTable, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalGameLogsTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    /**
     * Abstract in Parent Class
     * Merge Game Logs from Sync Original
     *
     * @param string $token token from sync Information, found in \Game_platform_manager::class@mergeGameLogs
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
        $table = $this->originalGameLogsTable;
        $sqlTime='ft.StateUpdateTs >= ? and ft.StateUpdateTs <= ?';
        if($use_bet_time){
            $sqlTime='ft.TransDate >= ? and ft.TransDate <= ?';
        }

        $sql = <<<EOD
SELECT
    ft.id as sync_index,
    ft.SportType as game_code,
    ft.SportType as sport_type,
    ft.ReferenceNo,
    ft.BetAmount,
    ft.BetAmount as bet_amount,
    ft.BetAmount as real_betting_amount,
    ft.TransDate,
    ft.MatchDate,
    ft.WinLoseStatus,
    ft.WinLoseStatus as status,
    JSON_UNQUOTE(ft.extra_info->'$.response_result_id') AS response_result_id,
    ft.Odds,
    ft.Transtype,
    ft.OddsType,
    ft.WinAmount as result_amount,
    ft.WinAmount,
    ft.StateUpdateTs,
    ft.DangerStatus,

    ft.external_unique_id as external_uniqueid,
    ft.updated_at,
    ft.md5_sum,

    ft.HomeTeamId home_team_id,
    ft.AwayTeamId away_team_id,
    ft.LeagueId league_id,
    ft.HomeScore home_score,
    ft.AwayScore away_score,
    ft.RunHomeScore run_home_score,
    ft.RunAwayScore run_away_score,
    ft.WinLoseStatus win_lose_status,
    ft.MatchId match_id,
    ft.TransType bet_type,

    ft.SocTransId soc_trans_id,

    ft.extra_info,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as ft
    LEFT JOIN game_description ON ft.SportType = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON ft.SourceName = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
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
        $betType = (array_key_exists($row['Transtype'],self::BET_TYPES)) ? self::BET_TYPES[$row['Transtype']] : null;

        if (isset($row['note'])) {
            $note = $row['note'];
        } else {
            if (!empty($row['bet_status'])) {
                $note = $row['bet_status'];
            } else {
                $note = $row['WinLoseStatus'];
            }
        }

        $extra = [
            'table' => $row['ReferenceNo'],
            'odds' => isset($row['Odds'])?$row['Odds']:null,
            'is_parlay' => isset($row['bet_type'])&&$row['bet_type']=='PAR'?Game_logs::DB_TRUE:Game_logs::DB_FALSE,
            'note' => $note,
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
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null # no after balance in API response
            ],
            'date_info' => [
                'start_at' => $row['TransDate'],
                'end_at' => $row['StateUpdateTs'],
                'bet_at' => $row['TransDate'],
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['ReferenceNo'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => $betType
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

        $betStatus = $row['DangerStatus'];

        switch($row['status']) {
            case self::GAME_STATUS_WIN_ALL:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case self::GAME_STATUS_WIN_HALF:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case self::GAME_STATUS_LOSE_ALL:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case self::GAME_STATUS_LOSE_HALF:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case self::GAME_STATUS_DRAW:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case self::GAME_STATUS_PENDING:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }

        switch($betStatus) {
            case self::BET_STATUS_CANCELLED:
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            case self::BET_STATUS_REJECTED:
                $row['status'] = Game_logs::STATUS_REJECTED;
                break;
            default:
                break;
        }

        /*$betDetails = [
            'transaction_id' => $row['ReferenceNo'],
            'odds' => $row['Odds'],
            'odd_type' => $oddsType,
            'home_team_name' => $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_TEAM,$row['home_team_id']),
            'away_team_name' => $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_TEAM,$row['away_team_id']),
            'leaque_name' => $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_LEAGUE,$row['league_id']),
            'sport_type' => $row['game_description_name'],
            'win_lose_status' => $row['win_lose_status'],
            'match_id' => $row['match_id'],
            'bet_type' => isset(self::BET_TYPES[$row['bet_type']])?self::BET_TYPES[$row['bet_type']]:null,
            'home_score' => $row['home_score'],
            'away_score' => $row['away_score'],
            'run_home_score' => $row['run_home_score'],
            'run_away_score' => $row['run_away_score'],
            'parlay_data' => [],
        ];*/
        $oddsType = !empty($row['OddsType']) && !empty(self::ODDS_TYPE[$row['OddsType']]) ? self::ODDS_TYPE[$row['OddsType']] : null;
        $betDetails = json_decode($row['extra_info'], true);
        $betDetails['sport_type'] = $row['game_description_name'];
        $betDetails['win_lose_status'] = $row['win_lose_status'];
        $betDetails['bet_type'] = !empty(self::BET_TYPES[$row['bet_type']]) ? self::BET_TYPES[$row['bet_type']] : null;
        $betDetails['odds_type'] = $oddsType;

        if($row['bet_type']=='PAR'){

        }

        $row['bet_details'] = $betDetails;

        if ($row['DangerStatus']==self::BET_STATUS_REJECTED) {
            $row['result_amount'] = $row['bet_amount'] = $row['real_betting_amount'] = 0;
        }

        $row['bet_status'] = !empty(self::WIN_LOST_STATUS_MAP[$row['WinLoseStatus']]) ? self::WIN_LOST_STATUS_MAP[$row['WinLoseStatus']] : null;
        
        /* if($row['WinLoseStatus'] == 'D'){
            $row['bet_amount'] = $row['real_betting_amount'] = 0;
        } */

        ###### START PROCESS BET AMOUNT CONDITIONS
        # get bet conditions for status
        $betConditionsParams = [];
        $betConditionsParams['bet_status'] = strtolower(str_replace(' ', '_', $row['bet_status']));

        # get bet conditions for win/loss
        $betConditionsParams['win_loss_status'] = null;
        $betConditionsParams['odds_status'] = null;

        if($row['bet_amount']>0){
            if ($row['result_amount'] < 0) {
                if ((abs($row['result_amount']) / $row['bet_amount']) == .5 ) {
                    $betConditionsParams['win_loss_status'] = 'half_lose';
                }
            } else {
                if (($row['result_amount'] / $row['bet_amount']) == .5 ) {
                    $betConditionsParams['win_loss_status'] = 'half_win';
                }
            }
        }

        # get bet conditions for odds
        $oddsType = $this->getUnifiedOddsType($row['OddsType']);
        $betConditionsParams['valid_bet_amount'] = $row['bet_amount'];
        $betConditionsParams['bet_amount_for_cashback'] = $row['bet_amount'];
        $betConditionsParams['real_betting_amount'] = $row['real_betting_amount'];
        $betConditionsParams['odds_type'] = $oddsType;
        $betConditionsParams['odds_amount'] = $row['Odds'];

        list($_appliedBetRules, $_validBetAmount, $_betAmountForCashback, $_realBettingAmount, $_betconditionsDetails, $note) = $this->processBetAmountByConditions($betConditionsParams);

        if (!empty($_appliedBetRules)) {
            $row['bet_amount'] = $_validBetAmount;
            $row['bet_for_cashback'] = $_betAmountForCashback;
            $row['real_betting_amount'] = $_realBettingAmount;
            $row['note'] = $note;
        }
        ###### /END PROCESS BET AMOUNT CONDITIONS
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
     * Generate API URL
     *
     * @param string $apiName
     * @param array $params
     *
     * @return
     */
    public function generateUrl($apiName,$params)
    {
        $apiUri = self::URI_MAP[$apiName];

        $url = rtrim($this->apiUrl,'/') . '/'.$apiUri;

        $urlPath = $url.'?'.http_build_query($params);

        if($apiName == self::API_setMemberBetSetting){
            if(isset($params['MemberSetting'])){
                $memberSetting = json_encode($params['MemberSetting']);
                unset($params['MemberSetting']);
                $urlPath = $url.'?'.http_build_query($params);
                $urlPath .= "&MemberSetting={$memberSetting}";
            }
        }

        $this->CI->utils->debug_log(__METHOD__.' >>>>>>>>',$urlPath);

        return $urlPath;
    }

    /**
     * @param $array $params
     * @return array
     */
    public function getHttpHeaders($params)
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Custom HTTP call
     * @param string $ch
     * @param array $params
     * @return void
    */
    protected function customHttpCall($ch, $params)
    {

        switch($this->method){
            case self::METHOD_POST:
                $json_data = json_encode($params);
                curl_setopt($ch,CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$json_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $this->CI->utils->debug_log(__METHOD__.' REQUEST FIELD ',$json_data);
            break;
        }

        $this->CI->utils->debug_log(__METHOD__.' REQUEST FIELD ',http_build_query($params));
    }

    /**
     * Change the password of player in our SBE
     *
     * Important Note: since game provider do not see the password of player, we cannot apply this method to change the passwword of player in our DB, because if we do that we cannot recover password of player in game provider
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

    /**
     * Authentication of Player
     *
     * @param string $token
     * @param string $method
     * @return mixed
     */
    public function callback($token,$method)
    {
        $message = "Error, method not found";
        $response = [
            'authenticate' => [
                'member_id' => [
                    [
                        null
                    ]
                ],
                'status_code' => [
                    [
                        2 # anything, other than 0
                    ]
                ],
                'message' => [
                    [
                        $message
                    ]
                ]
            ]
        ];
        if(in_array($method,self::CALLBACK_ENDPOINTS)){
            if($this->isTokenValid($token)){
                $playerInfo = parent::getPlayerInfoByToken($token);
                $playerName = isset($playerInfo['username']) ? $playerInfo['username'] : null;
                $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
                $isBlocked = $this->isBlocked($playerName);

                if($isBlocked){
                    $message = "Error, player is blocked";
                    if(isset($response['authenticate']['message'])){
                        $response['authenticate']['message'] = $message;
                    }
                }else{
                    if(isset($response['authenticate']['message'])){
                        $message = "Success";
                        $response['authenticate']['message'] = $message;

                        if(isset($response['authenticate']['member_id'])){
                            if(isset($response['authenticate']['member_id'])){
                                $response['authenticate']['status_code'] = self::CALLBACK_SUCCESS_CODE;
                            }
                            $response['authenticate']['member_id'] = $gameUsername;
                        }
                    }
                }

            }else{
                $message = "Error, Token is not valid";
                if(isset($response['authenticate']['message'])){
                    $response['authenticate']['message'] = $message;
                }
            }
            return $response;
        }else{
            return $response;
        }
    }

    public function isPlayerOnline($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerOnline',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'Method' => self::METHOD_IS_PLAYER_ONLINE,
            'PartnerKey' => $this->partnerKey,
            'UserName' => $gameUsername
        ];

        return $this->callApi(self::API_isPlayerOnline, $params, $context);
    }

    public function processResultForIsPlayerOnline($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName,self::API_isPlayerOnline);

        $result = ['response_result_id' => $responseResultId, 'loginStatus'=>false];

        if($success){
            if(isset($resultArr['Data']['online'])){
                $result['is_online']=$resultArr['Data']['IsOnline']==true;
            }else{
                //missing
                $success=false;
            }
        }
        return [$success, $result];
    }

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        ];

        $params = [
            'Method' => self::METHOD_IS_PLAYER_EXISTS,
            'PartnerKey' => $this->partnerKey,
            'UserName' => $gameUsername
        ];

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = [];

        if($success)
        {
            $result['exists'] = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            if(isset($resultArr['Data']))
            {
                if(!$resultArr['Data'])
                {
                    $success = true;
                    $result['exists'] = false;
                }else{
	        		$result['exists'] = false; #api other error code
	        	}
	        }else{
        		$result['exists'] = false;
        	}
	    }

        return [$success, $result];
    }

    /** -----------------------UTILS----------------------- */

    /**
     * Round down number, meaning 0.019 will be 0.01 instead round up 0.019 to 0.02
    */
    private function round_down($number,$precision = 3)
    {

        $fig = (int) str_pad('1', $precision, '0');

	    return (floor($number * $fig) / $fig);
    }

    /**
     * Check if token is valid
     *
     * @param string $token the token to validate
     *
     * @return boolean
    */
    public function isTokenValid($token)
    {
        $playerInfo = parent::getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    /**
     * Process Request if Success or not
     *
     */
    public function processResultBoolean($responseResultId,$resultArr,$playerName=null,$method=null)
    {
        $success = false;

        if(! empty($resultArr) && $resultArr['Code'] == self::CODE_SUCCESS || $resultArr['Code'] == self::CODE_USER_ALREADY_EXISTS){
            $success = true;
        }

        if(! $success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('SPORTSBOOK_FLASH_TECH Game Got Error! =========================> ',$responseResultId,'playerName ',$playerName,'result ',$resultArr,'method',$method);
        }

        $this->CI->utils->debug_log(__METHOD__. ' SPORTSBOOK_FLASH_TECH  =========================> ',$responseResultId,'playerName ',$playerName,'result ',$resultArr,'method',$method);

        return $success;
    }

    /**
    * Get Reason of Failed Transactions/Response
    *
    * @param string $apiErrorCode the code from API
    *
    * @return int $reasonCode the reason code from abstract_game_api.php
    *
    */
    public function getReasons($apiErrorCode)
    {
      switch($apiErrorCode){
        case -1000:
            $reasonCode = self::REASON_SERVER_TIMEOUT;
            break;
        case -999:
            $reasonCode = self::REASON_SERVER_EXCEPTION;
            break;
        case -103:
            $reasonCode = self::REASON_ACCESS_DENIED;
            break;
        case -102:
            $reasonCode = self::REASON_REQUEST_LIMIT;
            break;
        case -101:
            $reasonCode = self::REASON_API_MAINTAINING;
            break;
        case -100:
            $reasonCode = self::REASON_INVALID_ARGUMENTS;
            break;
        case -98:
            $reasonCode = self::REASON_NO_ENOUGH_BALANCE;
            break;
        case -97:
            $reasonCode = self::REASON_NOT_FOUND_PLAYER;
            break;
        case -96:
            $reasonCode = self::REASON_TRANSACTION_ID_ALREADY_EXISTS;
            break;
        case -95:
            $reasonCode = self::REASON_ILLEGAL_REQUEST;
            break;
        case -1:
            $reasonCode = self::REASON_FAILED_FROM_API;
            break;
         default:
            $reasonCode = self::REASON_UNKNOWN;
         break;
      }

      return $reasonCode;
    }

    /**
     * Generate Extra info in OGL
     *
     * @param array $record
     * @param int $responseResultId
     *
     * @return json
    */
    public function generateExtraInfoInOgl($record,$responseResultId)
    {
        $data = ['response_result_id'=>$responseResultId];

        if(isset($record['TransDate'])){
            $data['raw_trans_date'] = $record['TransDate'];
        }

        if(isset($record['MatchDate'])){
            $data['raw_match_date'] = $record['MatchDate'];
        }

        if(isset($record['StateUpdateTs'])){
            $data['raw_state_update_ts'] = $record['StateUpdateTs'];
        }

        return json_encode($data);
    }

    /**
     * Generate Extra info in OGL
     *
     * @param int $time
     *
     * @return datetime
    */
    public function convertTickIntoDateTime($time)
    {
        $dateTime = null;

        if(! empty($time)){
            date_default_timezone_set('Europe/London'); // GMT+0
            $s1 = $time; // Transdate GMT+8
            $s2 = 621355968000000000; // GMT+0 19700101, FIXED
            $NowTime = ($s1-$s2)/10000000;
            $dateTime =  date('Y-m-d H:i:s',$NowTime);
        }

        if (date('I', time()) == 1){
            $new_time = strtotime($dateTime . "-1hours");
            return date('Y-m-d H:i:s', $new_time);
        }else{
           return $dateTime;
        }
    }

    public function queryLanguageInfo($extra)
    {
        $this->CI->utils->debug_log('SPORTSBOOK FLASHTECH (queryLanguageInfo)', $extra);
        $id=isset($extra['id'])?$extra['id']:null;
        $type=$extra['type'];
        if(!$id){
            return ['sucess'=>false,'data'=>[]];
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryLanguageInfo',
            'type'=>$type,
            'id'=>$id
        ];

        $params = [
            'Method' => self::METHOD_LANGUAGE_INFO,
            'PartnerKey' =>  $this->partnerKey,
            'Type' => $type,
            'ID' => $id
        ];

        return $this->callApi(self::API_queryLanguageInfo, $params, $context);
    }

    public function processResultForQueryLanguageInfo($params) {
        $this->CI->load->library(['language_function']);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
		$id = $this->getVariableFromContext($params, 'id');
        $type = $this->getVariableFromContext($params, 'type');
        $this->CI->utils->debug_log('SPORTSBOOK FLASHTECH (processResultForQueryLanguageInfo)', $resultArr);

        $success = $this->processResultBoolean($responseResultId, $resultArr, null,self::API_queryLanguageInfo);
        $result['data'] = [];

        //set data in cache
        if(isset($resultArr['Data']) &&
        !empty($resultArr['Data']) &&
        is_array($resultArr['Data'])){
            //preprocess data
            $arr = [];
            $arr[LANGUAGE_FUNCTION::INT_LANG_ENGLISH]=isset($resultArr['Data']['en-US'])?$resultArr['Data']['en-US']:'';
            $arr[LANGUAGE_FUNCTION::INT_LANG_CHINESE]=isset($resultArr['Data']['zh-CN'])?$resultArr['Data']['zh-CN']:'';
            $arr[LANGUAGE_FUNCTION::INT_LANG_THAI]=isset($resultArr['Data']['th-TH'])?$resultArr['Data']['th-TH']:'';
            $arr[LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE]=isset($resultArr['Data']['vi-VN'])?$resultArr['Data']['vi-VN']:'';
            //$arr[Language_function::INT_LANG_ENGLISH]=isset($resultArr['Data']['ja-JP'])?$resultArr['Data']['ja-JP']:'';
            //$arr[Language_function::INT_LANG_ENGLISH]=isset($resultArr['Data']['zh-TW'])?$resultArr['Data']['zh-TW']:'';
            $arr[LANGUAGE_FUNCTION::INT_LANG_INDONESIAN]=isset($resultArr['Data']['id-ID'])?$resultArr['Data']['id-ID']:'';
            //$arr[Language_function::INT_LANG_ENGLISH]=isset($resultArr['Data']['es-ES'])?$resultArr['Data']['es-ES']:'';
            $arr[LANGUAGE_FUNCTION::INT_LANG_KOREAN]=isset($resultArr['Data']['ko-KR'])?$resultArr['Data']['ko-KR']:'';
            //$arr[Language_function::INT_LANG_ENGLISH]=isset($resultArr['Data']['ar-EG'])?$resultArr['Data']['ar-EG']:'';
            $arr[LANGUAGE_FUNCTION::INT_LANG_INDIA]=isset($resultArr['Data']['en-US'])?$resultArr['Data']['en-US']:'';
            $arr = '_json:' . json_encode($arr);
            $this->saveLanguageInfoToCache($type, $id, $arr);
            $result['data'] = $arr;
        }

        return [$success, $result];
    }

    public function queryParlayRecord($extra)
    {
        $this->CI->utils->debug_log('SPORTSBOOK FLASHTECH (queryParlayRecord)', $extra);
        $socTransID=isset($extra['SocTransId'])?$extra['SocTransId']:null;
        if(!$socTransID){
            return ['sucess'=>false,'data'=>[]];
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryParlayRecord',
            'socTransID'=>$socTransID,
        ];

        $params = [
            'Method' => self::METHOD_PARLAY_BET_RECORD,
            'PartnerKey' =>  $this->partnerKey,
            'SocTransID' => $socTransID,
        ];

        return $this->callApi(self::API_queryParlayRecord, $params, $context);
    }

    public function processResultForQueryParlayRecord($params) {
        $this->CI->load->library(['language_function']);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('SPORTSBOOK FLASHTECH (processResultForQueryParlayRecord)', $resultArr);

        $success = $this->processResultBoolean($responseResultId, $resultArr, null,self::API_queryParlayRecord);
        $result['data'] = [];

        //set data in cache
        if(isset($resultArr['Data']) &&
        !empty($resultArr['Data']) &&
        is_array($resultArr['Data'])){
            foreach($resultArr['Data'] as $parlayData){
                $temp = $parlayData;
                $temp['home_team_name'] = $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_TEAM,$parlayData['HomeId']);
                $temp['away_team_name'] = $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_TEAM,$parlayData['AwayId']);
                $temp['leaque_name'] = $this->getLanguageInfoFromCache(self::LANGUAGE_TYPE_LEAGUE,$parlayData['LeagueId']);
                $this->CI->utils->debug_log('SPORTSBOOK FLASHTECH (processResultForQueryParlayRecord)', 'parlayData', $parlayData);
                $result['data'][] = $temp;
            }
        }

        return [$success, $result];
    }

    public function saveLanguageInfoToCache($type, $id, $data){
        $key=$this->originalGameLogsTable.'-'.$type.'-'.$id;
        return $this->CI->utils->saveJsonToCache($key, $data);
    }

    public function getLanguageInfoFromCache($type, $id){
        $key=$this->originalGameLogsTable.'-'.$type.'-'.$id;
        $this->CI->utils->debug_log('SPORTSBOOK FLASHTECH (getLanguageInfoFromCache)', $key);
        $result = $this->CI->utils->getJsonFromCache($key);
        if(empty($result) || $this->always_ovewrite_cache){
            //get from api
            $extra=[];
            $extra['id']=(int)$id;
            $extra['type']=(int)$type;
            $this->queryLanguageInfo($extra);
            $result = $this->CI->utils->getJsonFromCache($key);
        }
        return $result;
    }

    /**
     * overview : set member settings
     *
     * @param $playerName
     * @return array
     */
    public function setMemberBetSetting($playerName) {
        if(empty($this->memberSetting)){
            return ["success" => false, array("result" => "empty setting!!")];
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForMemberSettings',
            'playerName' => $playerName,
        );
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $params = [
            'Method' => self::METHOD_UPDATE_USER_SETTING,
            'PartnerKey' =>  $this->partnerKey,
            'UserName' => $gameUsername,
            'MemberSetting' => $this->memberSetting
        ];
        return $this->callApi(self::API_setMemberBetSetting, $params, $context);
    }

    /**
     * overview : get member settings
     *
     * @param $playerName
     * @return array
     */
    public function getMemberBetSetting($playerName) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForMemberSettings',
            'playerName' => $playerName,
        );
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $params = [
            'Method' => self::METHOD_GET_USER_DEFAULT_BET_LIMIT,
            'PartnerKey' =>  $this->partnerKey,
            'UserName' => $gameUsername
        ];
        return $this->callApi(self::API_getMemberBetSetting, $params, $context);
    }

    /**
     * overview : process result for member settings
     *
     * @param $params
     * @return array
     */
    public function processResultForMemberSettings($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
        $result = array('response_result_id' => $responseResultId, 'result' => $resultJson);
        return array($success, $result);
    }

    public function updatePlayerBetLimitRange($request_params) {
        if (empty($request_params['game_username'])) {
            return [
                'success' => false,
                'message' => 'game_username required',
            ];
        }

        if (empty($request_params['member_setting'])) {
            return [
                'success' => false,
                'message' => 'member_setting required',
            ];
        }

        $game_username = $request_params['game_username'];
        $member_setting = $request_params['member_setting'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetLimitRange',
            'game_username' => $game_username,
        ];

        $params = [
            'Method' => self::METHOD_UPDATE_USER_SETTING,
            'PartnerKey' =>  $this->partnerKey,
            'UserName' => $game_username,
            'MemberSetting' => $member_setting
        ];

        $this->utils->debug_log(__METHOD__,$context, $params);

        return $this->callApi(self::API_setMemberBetSetting, $params, $context);
    }

    public function processResultForUpdatePlayerBetLimitRange($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

        $result = [
            'response_result_id' => $responseResultId,
            'result' => $resultJson,
        ];

        return array($success, $result);
    }
 }
   /** END OF FILE */