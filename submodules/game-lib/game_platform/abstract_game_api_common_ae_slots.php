<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt.php';

/**
 * API NAME: AE_SLOTS_GAMING_API
 * Ticket Number: OGP-12700
 * 
 * @see Ameba Integration API Documentation V1.14
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 abstract class Abstract_game_api_common_ae_slots extends Abstract_game_api {
    
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    private $original_gamelogs_table;
    private $method;
    private $generated_token;
    private $site_id;
    private $secret_key;
    private $prefix_for_username;
    private $exp;
    private $currentAPI;
    private $group;
  
    const CODE_SUCCESS = 'OK'; # all api endpoints have this "error_code":0 means request is ok 

    # this is API ACTIONS
    const CREATE_ACCOUNT = 'create_account';
    const REGISTER_TOKEN = 'register_token';
    const REQUEST_DEMO_PLAY = 'request_demo_play';
    const DEPOSIT = 'deposit';
    const WITHDRAW = 'withdraw';
    const GET_TRANSACTION = 'get_transaction';
    const GET_BALANCE = 'get_balance';
    const GET_BALANCES = 'get_balances';
    const GET_BET_HISTORIES = 'get_bet_histories';
    const GET_JACKPOT_METER = 'get_jackpot_meter';
    const GET_JACKPOT_WINS = 'get_jackpot_wins';
    const GET_LEADER_BOARD = 'get_leaderboard';
    const GET_GAME_LIST = 'get_game_list';
    const GET_PLAYING_GAMES = 'get_playing_games';
    const GET_FREEZE_PLAYER = 'freeze_player';
    const GET_UNFREEZE_PLAYER = 'unfreeze_player';
    const GET_FROZEN_PLAYERS = 'get_frozen_players';
    const GET_GAME_HISTORY_URL = 'get_game_history_url';

    # Other endpoints of AE SLOTS API
    const API_getJackpotMeter = 'getJackpotMeter';
    const API_getJackpotWins = 'getJackpotWins';
    const API_getLeaderBoard = 'getLeaderBoard';
    const API_getPlayingGames = 'getPlayingGames';
    const API_getFrozenPlayers = 'getFrozenPlayers';


    /** 
     * Fields in ae_slots_game_logs table, we want to detect changes for update in fields
     * 
     * account_name(string) - The player’s account name
     * currency(string) -  The player’s currency
     * game_id(int) - The game id
     * round_id(string) - Unique id for each game round
     * free(boolean) - The value will be true if the round is free, free spin means the bet does not deduct money from the player wallet
     * bet_amt(string) - The Bet amount of the game round
     * payout_amt(string) - The payout amount of the game round
     * completed_at(string) - The completed time in format YYYY-MM-DDThh:mm:ssTZD (+00:00 timezone)
     * rebate_amt(string) - <optional> The rebate amount to be added to the player’s account. Should be greater or equal zero
     * jp_pc_con_amt(string,precision:10) - <When jackpot enabled> The jackpot contribution amount in player’s currency
     * jp_jc_con_amt(string,precision:10) - <When jackpot enabled> The jackpot contribution amount in jackpot’s currency
     * jp_win_id(string) - <when hit jackpot> The jackpot win id
     * jp_pc_win_amt(string,precision:10) - <when jp_win_id exists> The jackpot win amount in player’s currency.
     * jp_jc_win_amt(string,precision:10) - <when jp_win_id exists> The jackpot win amount in jackpot’s currency
     * jp_win_lv(int) - <when jp_win_id exists> The jackpot win lev
     * jp_direct_play(boolean) - <when jp_win_id exists> If true, means the jp_pc_win_amt is added to the player wallet
     * prize_type(string) - <when prize exist in the round> (rpcash-a Red packet which prize is Cash)(rpfreespin - a Red Packet which prize is FreeSpin)
     * prize_amt(string) - <when prize exist in the round> (If the type is rpcash, the value is the amount of prize) (If the type is rpfreespin, the value is the number of rounds)
     * site_id(int) - <when used group in request parameters> The identification of the operator site for this record
     * 
     * 
     * @param constant MD5_FIELDS_FOR_ORIGINAL 
    */
    const MD5_FIELDS_FOR_ORIGINAL = [
        'account_name',
        'currency',
        'game_id',
        'round_id',
        'free',
        'bet_amt',
        'payout_amt',
        'completed_at',
        'rebate_amt',
        'jp_pc_con_amt',
        'jp_jc_con_amt',
        'jp_win_id',
        'jp_pc_win_amt',
        'jp_jc_win_amt',
        'jp_win_lv',
        'jp_direct_pay',
        'prize_type',
        'prize_amt',
        'site_id'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amt',
        'payout_amt'
    ];

    # Fields in game_logs table, we want to detect changes for merge, and when ae_slots_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        'result_amount',
        'username',
        'completed_at'

    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'result_amount'
    ];

    # This is the URI endpoints of API
    const URI_MAP = [
        self::API_createPlayer => '/ams/api',
        self::API_queryForwardGame => '/ams/api',
        self::API_depositToGame => '/ams/api',
        self::API_withdrawFromGame => '/ams/api',
        self::API_queryTransaction => '/ams/api',
        self::API_queryPlayerBalance => '/ams/api',
        self::API_batchQueryPlayerBalance => '/ams/api',
        self::API_syncGameRecords => '/dms/api',
        self::API_getJackpotMeter => '/jms/api',
        self::API_getJackpotWins => '/jms/api',
        self::API_getLeaderBoard => '/dms/api',
        self::API_queryGameListFromGameProvider => '/dms/api',
        self::API_getPlayingGames => '/ams/api',
        self::API_blockPlayer => '/ams/api',
        self::API_unblockPlayer => '/ams/api',
        self::API_getFrozenPlayers => '/ams/api',
        self::API_queryBetDetailLink => '/dms/api'
    ];

    public function __construct()
    {

        parent::__construct();

        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->api_url = $this->getSystemInfo('url','https://api-snd.fafafa3388.com');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->site_id = $this->getSystemInfo('site_id','2281');
        $this->secret_key = $this->getSystemInfo('secret_key','q79G26qA4obivTmjotuA4sYX7lWrmZ+V');
        $this->exp = strtotime('+ 7 minutes'); # expiration of JWT TOKEN, curent time + 7 minutes
        $this->currentAPI = null; # default as null
        $this->group = $this->getSystemInfo('group','');
        $this->generated_token = null;


        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

    protected function generateJwtToken($payload,$secret_key)
    {

        $jwt = new JWT;
        $generated_jwt_token = $jwt->encode($payload,$secret_key);

        return $generated_jwt_token;
    }
    
    /**
     * If your API required some headers on every request, we can add it to this method
     * 
     * if action is get_bet_histories, we must inclue Accept-Encoding header with value "gzip"
     *  
     * @param array $params
     * 
     * @return array $headers the headers of your request store in key => value pair
     */
    protected function getHttpHeaders($params)
    {

        if($this->currentAPI == self::API_syncGameRecords){
            $headers['Accept-Encoding'] = 'application/gzip';
        }

        $headers['Content-Type'] = 'application/json';
        $headers['Authorization'] = 'Bearer '.$this->generated_token;

        return $headers;
    }

    public function generateUrl($apiName,$params)
    {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url.$apiUri;

        if($this->method == self::METHOD_GET){
            return $url.'?'.http_build_query($params);
        }

        return $url;
    }

    protected function customHttpCall($ch,$params)
    {

        switch($this->method){
            case self::METHOD_POST:
                $json_data = json_encode($params);
                curl_setopt($ch,CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$json_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            break;
        }

        $this->CI->utils->debug_log('AE_SLOTS REQUEST FIELD ',http_build_query($params));
    }

    public function processResultBoolean($responseResultId,$resultArr,$playerName = null)
    {

        $success = false;

        if(! empty($resultArr) && $resultArr['error_code'] == self::CODE_SUCCESS){
            $success = true;
        }
        
        if(! $success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('AE_SLOTS Game Got Error! =========================> ',$responseResultId,'playerName ',$playerName,'result ',$resultArr);
        }
        
        return $success;
    }

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

        $params = [
            'action' => self::CREATE_ACCOUNT,
            'exp' => $this->exp,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername,
            'currency' => $this->currency_type
        ];

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            $this->method = self::METHOD_POST;

            $this->CI->utils->debug_log('AE_SLOTS createPlayer Params',$params,'AE_SLOTS createPlayer JWT Token',$this->generated_token);

            return $this->callApi(self::API_createPlayer,$params,$context);
        }
    }

    public function processResultForCreatePlayer($params)
    {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params,'playerId');
        $playerName = $this->getVariableFromContext($params,'playerName');
        $success = $this->processResultBoolean($responseResultId,$resultArr,$playerName);
        $result = ['response_result_id'=>$responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId,Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        return [$success,$result];
    }

    /** 
     * Game Launch
     * just call the register_token API, and it will return the game url with token
     * 
     * Example Game Url:
     * http://player.og.local/player_center/goto_common_game/5544/14
     * 
     * Example Trial Game Url:
     * http://player.og.local/player_center/goto_common_game/5544/14/request_demo_play
    */
    public function queryForwardGame($playerName, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = ($extra['game_mode'] == self::REQUEST_DEMO_PLAY) ? self::REQUEST_DEMO_PLAY : self::REGISTER_TOKEN; # request_demo_play is for testing purposes, no bet history is generated

        $params = [
            'action' => $game_mode,
            'exp' => $this->exp,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername,
            'lang' => $this->getLauncherLanguage($extra['language'])
        ];

        if(! empty($game_code)){
            $params['game_id'] = $game_code;
        }

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            $this->method = self::METHOD_POST;

            $this->CI->utils->debug_log('AE_SLOTS params for queryForwardGame >>>>>>>>>>> ',$params);
    
            return $this->callAPI(self::API_queryForwardGame,$params,$context);
        }
    }

    public function processResultForQueryForwardGame($params)
    {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params,'playerName');
        $success = $this->processResultBoolean($responseResultId,$resultArr,$playerName);

        $result = [
            'response_result_id' => $responseResultId
        ];
        $this->CI->utils->debug_log('AE_SLOTS params URL >>>>>>>>>>> ', $resultArr['game_url']);
        if($success){
            if(isset($resultArr['game_url'])){
                $result['url'] = $resultArr['game_url'];
            }else{
                // missing game url
                $success = false;
            }
        }

        return [$success,$result];
    }

    public function getLauncherLanguage($currentLang) 
    {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zhCN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'viVN';
                break;
            case "en-us":
                $language = 'enUS';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $language = 'thTH';
                break;
            case "th-th":
                $language = 'thTH';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
	}

    /** 
     * Deposit money to a player’s game account
     * 
     * @param string $playerName the username field in player table
     * @param int $amount the deposit amount
     * @param  int $transfer_secure_id the unique id for the transaction
    */
    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
       
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? $this->site_id.uniqid() : $transfer_secure_id;

        $external_transaction_id = 'DTG'.$external_transaction_id; # if first 3 chars is DTG, means withdraw

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id,
        ];

        $params = [
            'action' => self::DEPOSIT,
            'site_id' => $this->site_id,
            'exp' => $this->exp,
            'account_name' => $gameUsername,
            'amount' => $amount,
            'tx_id' => $external_transaction_id,
        ];

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            $this->method = self::METHOD_POST;

            $this->CI->utils->debug_log('AE_SLOTS depositToGame params >>>>>>>>>>>>>>>>',$params);
    
            return $this->callApi(self::API_depositToGame,$params,$context);
        }
    }

    /** 
     * Process the depositToGame method
     * 
     * @param array $params parameter from depositToGame method
     * 
     * @return array
    */
    public function processResultForDepositToGame($params)
    {
        $playername = $this->getVariableFromContext($params,'playerName');
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$playername);
        $statusCode = $this->getStatusCodeFromParams($params);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($arrayResult['error_code'], $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
                $result['reason_id'] = $this->getReasons($arrayResult['error_code']);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            }
        }

        return [$success,$result];
    }

    /** 
     * Get Reason of Failed Transactions/Response
     * 
     * PlayerNotFound - The player does not exist
     * 
     * AlreadyProcessed - The transaction with the same transaction id already exists, and the transaction details (player,amount,type) are the same
     * 
     * TransactionNotMatch -The transaction exists but with different details
     * 
     * InsufficientBalance - The player balance is less than the amount to withdraw
     * 
     * TransactionNotFound - The transaction is not found
     * 
     * JackpotNotFound - The jackpot id not found in the system
     * 
     * InvalidTimeRange - The jackpot id not found in the system
     * 
     * ParameterError - If the request parameter is invalid
     * 
     * @param string $apiErrorCode the code from API
     * 
    */
    public function getReasons($apiErrorCode)
    {
        
        switch($apiErrorCode){
            case 'PlayerNotFound':
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;
                break;
            case 'AlreadyProcessed':
                $reasonCode = self::REASON_TRANSACTION_PENDING;
                break;
            case 'TransactionNotMatch':
                $reasonCode = self::REASON_INVALID_TRANSACTION_ID;
                break;
            case 'InsufficientBalance':
                $reasonCode = self::REASON_INSUFFICIENT_AMOUNT;
                break;
            case 'TransactionNotFound':
                $reasonCode = self::REASON_TRANSACTION_NOT_FOUND;
                break;
            case 'JackpotNotFound':
                $reasonCode = self::REASON_TRANSACTION_NOT_FOUND;
                break;
            case 'InvalidTimeRange':
                $reasonCode = self::REASON_INVALID_TIME_RANGE;
                break;
            case 'ParameterError':
                $reasonCode = self::REASON_PARAMETER_ERROR;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    /** 
     * Withdraw money from a player’s game account
     * 
     * @param string $playerName the username field in player table
     * @param int $amount the deposit amount
     * @param  int $transfer_secure_id the unique id for the transaction
    */
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
       $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       $external_transaction_id = empty($transfer_secure_id) ? $this->site_id.uniqid() : $transfer_secure_id;

       $external_transaction_id = 'WFG'.$external_transaction_id; # if first 3 chars is WFG, means withdraw

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id,
        ];

        $params = [
            'action' => self::WITHDRAW,
            'site_id' => $this->site_id,
            'exp' => $this->exp,
            'account_name' => $gameUsername,
            'amount' => $amount,
            'tx_id' => $external_transaction_id,
        ];

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            $this->method = self::METHOD_POST;

            $this->CI->utils->debug_log('AE_SLOTS depositToGame params >>>>>>>>>>>>>>>>',$params);
    
            return $this->callApi(self::API_withdrawFromGame,$params,$context); 
        }
    }

    /** 
     * Process Result of withdrawFromGame method
     * 
     * @param array $params parameter from withdrawFromGame method
    */
    public function processResultForWithdrawFromGame($params)
    {
        $playername = $this->getVariableFromContext($params,'playerName');
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$playername);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        ];

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['reason_id'] = $this->getReasons($arrayResult['error_code']);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        return [$success,$result];
    }

    /** 
     * This method returns a deposit or withdraw transaction information by the transaction id
     * 
     * @param int $transactionId the transaction id of transaction
     * @param array extra fields
    */
    public function queryTransaction($transactionId, $extra)
    {
		$context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
        ];
        

        $params = [
            'action' => self::GET_TRANSACTION,
            'site_id' => $this->site_id,
            'type' => $this->getTransactionTypeBaseInId($transactionId),
            'tx_id' => $transactionId,
        ];

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            $this->method = self::METHOD_POST;

            $this->CI->utils->debug_log('AE_SLOTS queryTransaction params >>>>>>>>>>>>>>>>',$params);
    
            return $this->callApi(self::API_queryTransaction,$params,$context);
        }
    }

    /** 
     * Process Result for queryTransaction
     * 
     * @param array $params the params of queryTransaction method
     * 
     * @return array
    */
    public function processResultForQueryTransaction($params)
    {
        
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
        ];

        # set reason_id if possible
        if(isset($arrayResult['error_code'])){
            $result['reason_id'] = $this->getReasons($arrayResult['error_code']);
        }

        if($success){
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
            $this->CI->utils->debug_log('AE_SLOTS ERROR in processResultForQueryTransaction >>>>>>>>>>>>>',$external_transaction_id,$arrayResult);
        }

        $this->CI->utils->debug_log('AE_SLOTS processResultForQueryTransaction >>>>>>>>>>>>>',$arrayResult);

        return [$success, $result];
    }

    /**
     * Get Transaction Type based on transaction id
     * 
     * @param string $transaction_id the transaction id of transaction
     * 
     * @return string transaction_type deposit or withdraw
     */
    public function getTransactionTypeBaseInId($transaction_id)
    {
        
        if(! empty($transaction_id)){
            $transaction_id = substr($transaction_id,0,3);

            if($transaction_id == 'DTG'){
                $transaction_type = 'deposit';
            }elseif($transaction_id == 'WFG'){
                $transaction_type = 'withdraw';
            }else{
                $transaction_type = 'unknown';
            }

            return $transaction_type;
        }

        return null;
    }
    
    /** 
     * This method returns a player’s balance from the game system
     * 
     * @param string $playerName the player name in the system
    */
    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        ];

        $params = [
            'action' => self::GET_BALANCE,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername
        ];

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            $this->method = self::METHOD_POST;

            $this->CI->utils->debug_log('AE_SLOTS queryPlayerBalance params >>>>>>>>>>>>>>>>',$params);
    
            return $this->callApi(self::API_queryPlayerBalance,$params,$context);     
        }
    }

    /**
     * Process queryPlayerBalance method
     * 
     * @param array $params the params of queryPlayerBalance method
     * 
     */
    public function processResultForQueryPlayerBalance($params)
    {
        $playerName = $this->getVariableFromContext($params,'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$playerName);

         # set reason_id if possible
         if(isset($arrayResult['error_code'])){
            $result['reason_id'] = $this->getReasons($arrayResult['error_code']);
        }

        if($success){
            //$balance = empty($arrayResult['balance']) ? null : $arrayResult['balance'];
            $result['exists'] = true;
            $result['balance'] = $this->round_down(floatval($arrayResult['balance']));
        }else{
            $result['exists'] = null;
            $this->CI->utils->debug_log('AE_SLOTS ERROR in processResultForQueryPlayerBalance result is >>>>>>>>>>>>>>>>',$result);
        }

        return [$success,$result];
    }
    
    /** 
     * Round down number, meaning 0.019 will be 0.01 instead round up 0.019 to 0.02
    */
    private function round_down($number,$precision = 3)
    {

        $fig = (int) str_pad('1', $precision, '0');

	    return (floor($number * $fig) / $fig);
    }
    
    /**
     * This method returns players balances from the game system
     * 
     * @param array $playerNames the player names e.g ['test1','test2']
     * @param int $syncId sync id
     * 
     * @return mixed
     */
    public function batchQueryPlayerBalance($playerNames, $syncId = null)
    {
          $result = [
            'success' => false
          ];

          if(empty($playerNames)){
              $result['success'] = true;

              return $result;
          }

          if(! is_array($playerNames)){
              $playerNames = [$playerNames];
          }

          $game_usernames = array_filter(array_map(function($playerName){
                return $this->getGameUsernameByPlayerUsername($playerName);
          },$playerNames));

         $batches = array_chunk($game_usernames,150);

         foreach($batches as $batch){
             
            $game_usernames_str = implode(',',$batch);
            
            $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultForBatchQueryPlayerBalance',
            ];

            $params = [
                'action' => self::GET_BALANCES,
                'site_id' => $this->site_id,
                'account_names' => $game_usernames_str # delimited by comma, e.g 'test1','test2'
            ];

            $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

            if($this->generated_token){
                $this->method = self::METHOD_POST;

                $this->CI->utils->debug_log('AE_SLOTS batchQueryPlayerBalance params >>>>>>>>>>>>>>>>',$params);
    
                $result =  $this->callApi(self::API_batchQueryPlayerBalance,$params,$context);
            }
         }

         return $result;
    }

    /** 
     * Process Result of batchQueryPlayerBalance method
     * 
    */
    public function processResultForBatchQueryPlayerBalance($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$arrayResult);

        if($success && isset($arrayResult['players']) && (! empty($arrayResult['players']))){
            $players = $arrayResult['players'];
            $self = $this;

            foreach($players as $player){
                $playerId = $this->getPlayerIdInGameProviderAuth($player['account_name']);
                $balance = $this->round_down($player['balance']);

                if($playerId){
                    $self->updatePlayerSubwalletBalance($playerId, floatval($balance));
                }
            }
        }

        return [$success];
    }

    /** 
     * Sync Original Game Logs
     * 
     * Note: According to Provider, they only store data for 60 days.
     * 
     * @param boolean $token
    */
    public function syncOriginalGameLogs($token = false)
    {
      $startDate = clone parent::getValueFromSyncInfo($token,'dateTimeFrom');
      $endDate = clone parent::getValueFromSyncInfo($token,'dateTimeTo');
      $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
      $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
      $startDateTime->modify($this->getDatetimeAdjust());

      # we should format our datetime according to API start and end time params
      $queryDateTimeStart = $startDateTime->format("Y-m-d\TH:i:s")."+00:00";
      $queryDateTimeEnd = $endDateTime->format('Y-m-d\TH:i:s')."+00:00";
      
      $result[] = $this->CI->utils->loopDateTimeStartEnd($queryDateTimeStart,$queryDateTimeEnd,'+15 minutes',function($queryDateTimeStart,$queryDateTimeEnd) {
         # we should format our datetime according to API start and end time params
        $queryDateTimeStart = $queryDateTimeStart->format("Y-m-d\TH:i:s")."+00:00";
        $queryDateTimeEnd = $queryDateTimeEnd->format('Y-m-d\TH:i:s')."+00:00";
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
          ];
          
          $params = [
            'action' => self::GET_BET_HISTORIES,         
            'from_time' => $queryDateTimeStart,
            'to_time' => $queryDateTimeEnd,
          ];
          
          if(empty($this->group)){
            $params['site_id'] = $this->site_id;
          }else{
            $params['group'] = $this->group; # this is an optional param, it it's exists, site_id param is ignored,
          }
         
          $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

          if($this->generated_token){
            $this->method = self::METHOD_POST;
            $this->currentAPI = self::API_syncGameRecords; # set this for to Accept-Encoding : application/gzip in header
    
            $this->CI->utils->debug_log('AE_SLOTS syncOriginalGameLogs params >>>>>>>>>>>>>>>>',$params);
      
            return $this->callApi(self::API_syncGameRecords,$params,$context);
          } 

      });
              
      return array("success" => true, "results"=>$result);
    }

    /** 
     * Process Result of syncOriginalGameLogs method
    */
    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);
        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        if($success){
            $gameRecords = $arrayResult['bet_histories'];
            $this->processGameRecords($gameRecords, $responseResultId);
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

            $this->CI->utils->debug_log('AE Slots after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

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

        return [$success,$dataResult];
    }

    public function processGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords)){
            
            foreach($gameRecords as $index => $record){
                $data['account_name'] = isset($record['account_name']) ? $record['account_name'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
                $data['round_id'] = isset($record['round_id']) ? $record['round_id'] : null;
                $data['free'] = isset($record['free']) ? $record['free'] : null;
                $data['bet_amt'] = isset($record['bet_amt']) ? ((double)$record['bet_amt']) : null;
                $data['payout_amt'] = isset($record['payout_amt']) ? ((double)$record['payout_amt']) : null;
                $data['completed_at'] = isset($record['completed_at']) ? $this->gameTimeToServerTime($record['completed_at']) : null;
                $data['rebate_amt'] = isset($record['rebate_amt']) ? $record['rebate_amt'] : null;
                $data['jp_pc_con_amt'] = isset($record['jp_pc_con_amt']) ? $record['jp_pc_con_amt'] : null;
                $data['jp_jc_con_amt'] = isset($record['jp_jc_con_amt']) ? $record['jp_jc_con_amt'] : null;
                $data['jp_win_id'] = isset($record['jp_win_id']) ? $record['jp_win_id'] : null;
                $data['jp_pc_win_amt'] = isset($record['jp_pc_win_amt']) ? $record['jp_pc_win_amt'] : null;
                $data['jp_jc_win_amt'] = isset($record['jp_jc_win_amt']) ? $record['jp_jc_win_amt'] : null;
                $data['jp_win_lv'] = isset($record['jp_win_lv']) ? $record['jp_win_lv'] : null;
                $data['jp_direct_pay'] = isset($record['jp_direct_pay']) ? $record['jp_direct_pay'] : null;
                $data['prize_type'] = isset($record['prize_type']) ? $record['prize_type'] : null;
                $data['prize_amt'] = isset($record['prize_amt']) ? $record['prize_amt'] : null;
                $data['site_id'] = isset($record['site_id']) ? $record['site_id'] : null;
                $data['after_balance'] = isset($record['end_balance']) ? $record['end_balance'] : 0;
                # default data
				$data['external_uniqueid'] = $data['account_name'].'_'.$data['round_id'];
                $data['response_result_id'] = $responseResultId;
                
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
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    /** 
     * Merge Game Logs from Original Game Logs Table
    */
    public function syncMergeToGameLogs($token)
    {
       $enabled_game_logs_unsettle = false;
       return $this->commonSyncMergeToGameLogs($token,
        $this,
        [$this,'queryOriginalGameLogs'],
        [$this,'makeParamsForInsertOrUpdateGameLogsRow'],
        [$this, 'preprocessOriginalRowForGameLogs'],
        $enabled_game_logs_unsettle
       );
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
        // only on time field `completed_at`
        $sqlTime = 'original.completed_at >= ? AND original.completed_at <= ?';

        $sql = <<<EOD
            SELECT
                original.id as sync_index,
                original.account_name as username,
                original.round_id as round,
                original.response_result_id,
                original.bet_amt as bet_amount,
                original.payout_amt as result_amount,
                original.after_balance,
                original.completed_at,
                original.game_id as game_code,
                original.external_uniqueid,
                original.md5_sum,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM $this->original_gamelogs_table as original
            LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND
            gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.account_name = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id = ?
            WHERE
            {$sqlTime}
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    }

    /** 
     * 
    */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' => $row['round']
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
                'game_type' => null, # //set game_type to null unless we know exactly game type name from original game logs
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['completed_at'],
                'end_at' => $row['completed_at'],
                'bet_at' => $row['completed_at'],
                'updated_at' => $this->CI->utils->getNowForMysql()
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
     * Prepare Original rows, include process unknown game, pack bet details, convert game status
     *
     * @param array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        $this->CI->load->model(array('game_logs'));
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        # we process unknown game here
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        # for now we only need, round id for bet details, we use get_bet_detail_link_of_ae_slots method in async.php
        $is_game_transaction = ($row['game_code'] == 'game transaction') ? false : true;
        
        $bet_details = [
            'roundId' => $row['round'],
            'gameUsername' => $row['username'],
            'isBet' => $is_game_transaction
        ];
        
        $row['game_description_id' ]= $game_description_id;
        $row['game_type_id'] = $game_type_id;
        $row['bet_details'] = $bet_details;
        $row['status'] = Game_logs::STATUS_SETTLED;
        $row['result_amount'] = $row['result_amount'] - $row['bet_amount'];
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
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

   /**
    * This method returns jackpot meters with currencies
    * 
    * @param string $jackpot_id the identification of the jackpot
    * @param array $currency|array The currency of the return jackpot meter.If send an array of currencies such as [‘HKD’, ‘CNY‘], 
    * the function will be return the jackpot meters of the HKD and CNY currencies
    * 
    * @return object
    * - error_code string - the error code
    * - jackpot_meters object - Each object included the ‘currency’, ‘exchange_rate’, ‘meters’.
    * If the request parameter of currency is an array, it will be an array of the meter object
    * - exchange_rate string - The exchange rate of the currency
    * - meter array - The sorted meter values
    */
    public function getJackpotMeter($jackpot_id,array $currency)
    {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetJackpotMeter',
            'jackpot_id' => $jackpot_id
        ];

        $params = [
            'action' => self::GET_JACKPOT_METER,
            'site_id' => $this->site_id,
            'jackpot_id' => $jackpot_id,
            'currency' => $currency

        ];

        $this->CI->utils->debug_log('AE_SLOTS getJackpotMeter params ===========> ',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){

          $this->method = self::METHOD_POST;

          return $this->callApi(self::API_getJackpotMeter,$params,$context);
        } 

    }

    /**
     * Process getJackpotMeter method
     * 
     * @return array
     */
    public function processResultForGetJackpotMeter($params){
        $jackpot_id = $this->getVariableFromContext($params, 'jackpot_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $status = $arrayResult['error_code']; #error code from API
        $result = [
            'jackpot_id' => $jackpot_id,
            'response_result_id' => $responseResultId,
            'reason_id' => $this->getReasons($status)
        ];

        return [$success,$result];
    }

    /** 
     * This method returns jackpot wins records with currencies
     * 
     * @param string $jackpot_id The identification of the Jackpo
     * @param string $from_time YYYY-MM-DDThh:mm:ssTZD, the lower boundary of the time range
     * @param string $to_time YYYY-MM-DDThh:mm:ssTZD, the upper boundary of the time range. to_time - from_time must <= 180days
     * @param string $win_lv <option> the win level of the jackpot win
     * @param string $currency <option> The currency of the return jackpot amount in histories. if it is null, will be used the default currency of the jackpot
     * 
     * @return object
    */
    public function getJackpotWins($jackpot_id,$from_time,$to_time,$win_lv,$currency)
    {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetJackpotWins',
            'jackpot_id' => $jackpot_id
        ];

        $params = [
            'action' => self::GET_JACKPOT_WINS,
            'site_id' => $this->site_id,
            'jackpot_id' => $jackpot_id,
            'from_time' => $from_time,
            'to_time' => $to_time,
            'win_lv' => $win_lv,
            'currency' => $currency

        ];

        $this->CI->utils->debug_log('AE_SLOTS getJackpotWins params ===========> ',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
          $this->method = self::METHOD_POST;

          return $this->callApi(self::API_getJackpotWins,$params,$context);
        } 
    }

    /**
     * Process getJackpotWins method
     * 
     * @return array
     */
    public function processResultForGetJackpotWins($params){
        $jackpot_id = $this->getVariableFromContext($params, 'jackpot_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $status = $arrayResult['error_code']; #error code from API
        $result = [
            'jackpot_id' => $jackpot_id,
            'response_result_id' => $responseResultId,
            'reason_id' => $this->getReasons($status)
        ];

        return [$success,$result];
    }

    /** 
     * This method returns jackpot wins records with currencies
     * 
     * @param string $type The type of the leaderboard, By now, only ‘bigwin’ available
     * @param string $from_time The start time of the records will be counted
     * @param string $to_time The end time of the records will be counted
     * @param string $min_amount The minimum amount in the leaderboard, default is "0"
     * 
     * @return object
    */
    public function getLeaderBoard($type,$from_time,$to_time,$min_amount)
    {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetLeaderboard'
        ];

        $params = [
            'action' => self::GET_LEADER_BOARD,
            'site_id' => $this->site_id,
            'type' => $type,
            'from_time' => $from_time,
            'to_time' => $to_time,
            'min_amount' => $min_amount

        ];

        $this->CI->utils->debug_log('AE_SLOTS getJackpotWins params ===========> ',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_getLeaderBoard,$params,$context);
        } 
    }

    /**
     * Process getLeaderBoard method
     * 
     * @return array
     */
    public function processResultForGetLeaderboard($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $status = $arrayResult['error_code']; #error code from API
        $result = [
            'response_result_id' => $responseResultId,
            'reason_id' => $this->getReasons($status)
        ];

        return [$success,$result];
    }

    /** 
     * This method returns the available game list of a site
     * 
     * @return object
    */
    public function queryGameListFromGameProvider($extra=null)
    {

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameProviderGamelist',
        ];

        $params = [
            'action' => self::GET_GAME_LIST,
            'site_id' => $this->site_id,
        ];

        $this->CI->utils->debug_log('AE_SLOTS queryGameListFromGameProvider params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
        } 
    }

    /** 
     * Process Result of queryGameListFromGameProvider
    */
    public function processResultForGetGameProviderGamelist($params)
    { 
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            $this->CI->load->library(['language_function']);
            $this->CI->load->model(['player_model']);
            $gameTypeCode='slots';
            //process game list
            //only one game type
            $result['game_type_list']=[
                [
                    'game_platform_id'=>$this->getPlatformCode(),
                    'game_type_unique_code'=>$gameTypeCode,
                    'game_type_name_detail'=>buildLangDetail('Slots', '老虎机'),
                    'game_type_status'=>Player_model::DB_BOOL_MAP[Player_model::DB_TRUE],
                ],
            ];
            $gameListArr=$arrayResult['games'];
            if(!empty($gameListArr)){
                $result['game_list']=[];
                foreach ($gameListArr as $gameInfo) {
                    $result['game_list'][]=[
                        'game_platform_id'=>$this->getPlatformCode(),
                        'game_unique_code'=>$gameInfo['id'],
                        'in_flash'=>null,
                        'in_html5'=>null,
                        'in_mobile'=>null,
                        'available_on_android'=>null,
                        'available_on_ios'=>null,
                        'game_status'=>Player_model::DB_BOOL_MAP[Player_model::DB_TRUE],
                        'progressive'=>null,
                        'enabled_freespin'=>$gameInfo['free_spin'],
                        'game_type_unique_code'=>$gameTypeCode,
                        'game_type_status'=>Player_model::DB_BOOL_MAP[Player_model::DB_TRUE],
                        'game_name_detail'=>buildLangDetail($gameInfo['locale']["enUS"]['name'],$gameInfo['locale']["zhCN"]['name']),
                        'game_type_name_detail'=>buildLangDetail('Slots', '老虎机'),
                    ];
                }
            }
        }

        return [$success,$result];
    }

    /**
     * This API returns the list of the games being played by a specific player
     * 
     * @param string $playername the player account name
     */
    public function getPlayingGames($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetPlayingGames',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];

        $params = [
            'action' => self::GET_PLAYING_GAMES,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername
        ];

        $this->CI->utils->debug_log('AE_SLOTS getPlayingGames params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_getPlayingGames, $params, $context);
        } 
    }

    /**
     * Process Result of getPlayingGames method
     * 
     */
    public function processResultForGetPlayingGames($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = ['response_result_id' => $responseResultId];
        
        return [$success, $result];
    } 

    /** 
     * Freeze the player account. Frozen players can not bet
    */
    public function blockPlayer($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];

        $params = [
            'action' => self::GET_FREEZE_PLAYER,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername,
            // 'period' =>  <optional> The freeze time period (second). 0 < period <= 86400 (24 hours) Default value is 3600 (1 hour)
        ];

        $this->CI->utils->debug_log('AE_SLOTS blockPlayer params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_blockPlayer, $params, $context);
        } 
    }

    /** 
     * Process Result of processResultForGetPlayingGames method
    */
    public function processResultForBlockPlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName); 

        if($success){
            $this->blockUsernameInDB($gameUsername);//block on OG 
            $success = true;  
        }
        return array($success, $arrayResult);

    }

    /** 
     * Unfreeze the frozen player account
    */
    public function unblockPlayer($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnblockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];

        $params = [
            'action' => self::GET_UNFREEZE_PLAYER,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername
        ];

        $this->CI->utils->debug_log('AE_SLOTS unblockPlayer params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_unblockPlayer, $params, $context);
        } 
    }

    /** 
     * Process Result of unblockPlayer method
    */
    public function processResultForUnblockPlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName); 

        if($success){
            $$success = $this->unblockUsernameInDB($gameUsername);;//unblock on OG 
			$success = true;
        }

        return array($success, $arrayResult);

    }

    /** 
     * This method returns frozen players with their freeze end time
    */
    public function getFrozenPlayers($playerName)
    {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForFrozenPlayers'
        ];

        $params = [
            'action' => self::GET_FROZEN_PLAYERS,
            'site_id' => $this->site_id
        ];

        $this->CI->utils->debug_log('AE_SLOTS unblockPlayer params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_unblockPlayer, $params, $context);
        } 
    }

    /** 
     * Process Result of getFrozenPlayers method
    */
    public function processResultForFrozenPlayers($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult); 


        return array($success, $arrayResult);
    }

    /** 
     * Create a game history login token for open game history.
    *  The api will return the game history url included the generated token and other parameters
    */
    public function queryBetDetailLink($gameUsername, $round_id = null, $extra = null)
    {        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink'
        ];
        
        $params = [
            'action' => self::GET_GAME_HISTORY_URL,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername,
            'round_id' => $round_id,
            // 'lang' => 'enUS' <optional> The display language in the game history. 
            // Supported options are ‘enUS’, ‘zhTW’, ‘zhCN’, default value: ‘enUS’
        ];

        $this->CI->utils->debug_log('AE_SLOTS unblockPlayer params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_queryBetDetailLink, $params, $context);
        } 
    }

    /** 
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult); 
        $response = [];
        if(isset($arrayResult['game_history_url']) && !empty($arrayResult['game_history_url'])){
            $response['url'] = $arrayResult['game_history_url'];
        }
        return array($success, $response);
    }
 }
 /** end of file */