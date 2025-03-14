<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__). "/abstract_game_api_common_evolution_gaming.php";

/**
 * API name: EVOLUTION_SEAMLESS_THB1_API
 * Ticket Number: OGP-14918
 * Wallet type: Single Wallet/Seamless
 *
 * @see Evolution Live Casino Integration Manual(One Wallet/Seamless) - exported on 17/8/2018
 * @category Game API
 * @copyright 2013 - 2022 tot
 * @author Jason Miguel
 */

class Game_api_evolution_seamless_thb1_api extends Abstract_game_api_common_evolution_gaming
{
    const CURRENCY_TYPE = "THB";
    const IS_PROCESSED = 1;

    protected $playerUpdateDetails;
    protected $brandId;
    protected $brandSkin;
    protected $channelWrapped;
    protected $channelMobile;
    protected $urlCashier;
    protected $urlResponsibleGaming;
    protected $urlLobby;
    protected $urlSessionTimeout;
    protected $urlGameHistory;
    protected $urlRealityCheckURL;
    protected $transaction_table_name;
    public $currency;
    public $is_support_lobby; // for sub providers.
    public $currentMethod=null;
    public $original_seamless_wallet_transactions_table = 'evolution_seamless_thb1_wallet_transactions';
    public $prefix_for_username;


    const ACTION_DEBIT = 'debit';
    const ACTION_CREDIT = 'credit';
    const ACTION_CANCEL = 'cancel';

    public function __construct()
    {
        parent::__construct();

        $this->currency = $this->getSystemInfo('currency');
        $this->language_code = $this->getSystemInfo('language_code');
        $this->country_code = $this->getSystemInfo('country_code');

        $this->game_logs_url = $this->getSystemInfo('game_logs_url');
        $this->game_logs_api_password = $this->getSystemInfo('api_token');
        $this->playerUpdateDetails = $this->getSystemInfo('playerUpdateDetails',true);
        $this->brandId = $this->getSystemInfo('brandId',null);
        $this->brandSkin = $this->getSystemInfo('brandSkin',null);
        $this->channelWrapped = $this->getSystemInfo('channelWrapped',false);
        $this->channelMobile = $this->getSystemInfo('channelMobile',null);
        $this->urlCashier = $this->getSystemInfo('urlCashier',null);
        $this->urlResponsibleGaming = $this->getSystemInfo('urlResponsibleGaming',null);
        $this->urlLobby = $this->getSystemInfo('urlLobby',null);
        $this->urlSessionTimeout = $this->getSystemInfo('urlSessionTimeout',null);
        $this->urlGameHistory = $this->getSystemInfo('urlGameHistory',null);
        $this->urlRealityCheckURL = $this->getSystemInfo('urlRealityCheckURL',null);
        $this->vipGroupID = $this->getSystemInfo('vipGroupID',null);
        $this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore',true);
        $this->dummy_ipv4_for_ipv6 = $this->getSystemInfo('dummy_ipv4_for_ipv6','119.9.106.90');
        $this->transaction_table_name = $this->getTransactionsTable();
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data',true);
        
        $this->launcher_mode = $this->getSystemInfo('launcher_mode','lobbyAndSingle');

        $this->encryption_key = $this->getSystemInfo('encryption_key', 'TZYcjOKcF0');        
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', '4SaWUYELU8');        
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        
        $this->default_lobby_table = $this->getSystemInfo('default_lobby_table', null);
        
        $this->game_vertical = $this->getSystemInfo('game_vertical', 'slots,live,rng');
        
        $this->seamless_api_return_balance_no_quote = $this->getSystemInfo('seamless_api_return_balance_no_quote', false);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->enable_skip_credit_player_validation = $this->getSystemInfo('enable_skip_credit_player_validation', false);
    }

    /**
     * Get Platform code of Game API
     *
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return EVOLUTION_SEAMLESS_THB1_API;
    }

    /**
     *
     * @return string original game logs table in database
     *
     * @return string the original game logs table
     */
    public function getOriginalTable()
    {
        return 'evolution_seamless_thb1_game_logs';
    }

    /**
     * Determine if Game is Seamless or Transfer wallet
     *
     * @return boolean
    */
    public function isSeamLessGame()
    {
        return true;
    }

    protected function customHttpCall($ch, $params) {

        $this->CI->utils->debug_log('EVOLUTION_SEAMLESS_THB1_API GAME API PASS =============>' . $this->game_logs_api_password);

        if(isset($params['startDate'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt($ch, CURLOPT_USERPWD, $this->casino_key.':'.$this->game_logs_api_password);
        }elseif(isset($params['gameVertical'])){
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt($ch, CURLOPT_USERPWD, $this->casino_key.':'.$this->external_lobby_api_token);
        }elseif ($this->method = self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    /**
     * Generate URL
    */
    public function generateUrl($apiName,$params)
    {
        if($apiName == self::API_triggerInternalPayoutRound){
            $token = $this->getSystemInfo('auth_token');
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/evolution_service_api/credit?authToken={$token}";
            return $url;
        }
        $api_uri = self::URI_MAP[$apiName];

        $this->api_url = trim($this->api_url, '/');

        if ($apiName == self::API_login) {
            $url = $this->api_url.'/'.$api_uri.$this->casino_key.'/'.$this->api_token;
        } elseif ($apiName == self::API_syncGameRecords) {
            $url = $this->game_logs_url.$api_uri.'?startDate='.$params['startDate'] . '&endDate=' . $params['endDate'];
        } elseif ($apiName == self::API_queryGameListFromGameProvider) {
            $url = $this->game_list_api_url . '/' . $api_uri . '/' . $this->casino_key . '/state?' . http_build_query($params);
        } else {
            $url = $this->api_url . '/' . $api_uri . '?' . http_build_query($params);
        }

        $this->CI->utils->debug_log('EVOLUTION_SEAMLESS_THB1_API GAME URL =============>' . $url);

        return $url;
    }

    /**
     * If your API required some headers on every request, we can add it to this method
     *
     * our header Content-type should always be application/json
     *
     * @param array $params
     *
     * @return array $headers the headers of your request store in key => value pair
     */
    protected function getHttpHeaders($params)
    {
        $headers['Content-Type'] = 'application/json';

        if($this->currentMethod != null && $this->currentMethod=self::API_queryGameListFromGameProvider){
            $username = $this->casino_key;
            $password = $this->external_lobby_api_token;
            $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        }
        return $headers;
    }

    /**
     * Since this is seamless, we only create player in our table game_provider_auth
     * not in game provider
     *
     */
    public function createPlayer($playerName,$playerId,$password,$email=null,$extra=null)
    {
        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ];

        $return = Abstract_game_api::createPlayer($playerName,$playerId,$password,$email,$extra);
        $success = false;
        $message = "Unable to create account for EVOLUTION_SEAMLESS_THB1_API";

        if($return){
           $success = true;
           $message = "Success to create account for EVOLUTION_SEAMLESS_THB1_API";
        }

        $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API createPlayer is:",$success);

        return [
          "success" => $success,
          "message" => $message
        ];
    }

    /**
     * Since this is seamless, the transaction like deposit is only save in table playeraccount
    */
    public function depositToGame($playerName,$amount,$transfer_secure_id=null)
    {
      $external_transaction_id = $transfer_secure_id;

      $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API depositToGame");

      return [
         "success" => true,
         "external_transaction_id" => $external_transaction_id,
         "response_result_id" => null,
         "didnot_insert_game_logs" => true
      ];
    }

    /**
     * Since this is seamless, the transaction like withdraw is only save in table playeraccount
    */
    public function withdrawFromGame($playerName,$amount,$transfer_secure_id=null)
    {
      $external_transaction_id = $transfer_secure_id;

      $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API withdrawFromGame");

      return [
         "success" => true,
         "external_transaction_id" => $external_transaction_id,
         "response_result_id" => null,
         "didnot_insert_game_logs" => true
      ];
    }

    /**
     * Query Transaction
     */
    public function queryTransaction($transactionId,$extra)
    {
       return $this->returnUnimplemented();
    }

    /**
     *
     */
    public function isPlayerExist($playerName)
    {
        return Abstract_game_api::isPlayerExist($playerName);
    }

    /**
     * Query Player Balance
    */
    public function queryPlayerBalance($playerName)
    {
      $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
      $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
      $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

      $result = array(
          'success' => true,
          'balance' => $balance
      );

      return $result;
    }

    public function queryPlayerBalanceByPlayerId($playerId)
    {
      $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

      $result = array(
          'success' => true,
          'balance' => $balance
      );

      return $result;
    }

    /**
     *
    */
    public function login($playerName,$extra=null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $uuid = random_string("alnum",50);
        $ip_address = $this->CI->input->ip_address();
        if (!filter_var($ip_address, FILTER_FLAG_IPV4)) {
          $this->CI->utils->debug_log("EVOLUTION_SEAMLESS_THB1_API IP Address is not ipv4",$ip_address);
          $ip_address = $this->dummy_ipv4_for_ipv6;
        }
        $category = isset($extra["game_type"]) ? $extra["game_type"] : null;
        $table_id = isset($extra["game_code"]) && !empty($extra["game_code"]) ? $extra["game_code"] : $this->default_lobby_table;
        $is_mobile = isset($extra["is_mobile"]) ? $extra["is_mobile"] : null;

        $language = $this->language_code;
        if(isset($extra["language"]) && ! is_null($extra["language"]) && ($extra["language"] != "null")){
            $language = $this->getLauncherLanguage($extra['language']);
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        # generate player session
        $sessionArr = [
            'token'=>$this->getPlayerTokenByUsername($playerName),
            'currency'=>$this->currency,
            'player_username'=>$playerName,
            'player_gameusername'=>$gameUsername,
            'game_platform_id'=>$this->getPlatformCode()
        ];
        $playerSessionId=$this->generateSessionId($sessionArr);

        $params = [
            "uuid" => $uuid,
            "player" => [
                "id" => $gameUsername,
                "update" => $this->playerUpdateDetails,
                "firstName" => $gameUsername,
                "lastName" => $gameUsername,
                "country" => $this->country_code,
                "language" => $language,
                "currency" => $this->currency,
                "session" => [
                    "id" => $playerSessionId,
                    "ip" => $ip_address
                ]
            ],
            "config" => [
                "game" => [
                    "category" => $category,
                    "table" => [
                        "id" => $table_id
                    ]
                ]
            ]
        ];
        

        if(is_null($table_id)){
            $params["config"]["game"] = null;
        }

        if(! is_null($this->brandId)){
            $params["config"]["brand"]["id"] = $this->brandId;
        }


        if(! is_null($this->brandSkin)){
            $params["config"]["brand"]["skin"] = $this->brandSkin;
        }

        $params["config"]["channel"]["wrapped"] = false;
        if($this->channelWrapped){
            $params["config"]["channel"]["wrapped"] = true;
        }

        $params["config"]["channel"]["mobile"] = false;
        if($is_mobile){
            $params["config"]["channel"]["mobile"] = true;
        }

        if(! is_null($this->urlCashier)){
            $params["config"]["urls"]["cashier"] = $this->urlCashier;
        }

        if(! is_null($this->urlCashier)){
            $params["config"]["urls"]["responsibleGaming"] = $this->urlResponsibleGaming;
        }

        if(! is_null($this->urlCashier)){
            $params["config"]["urls"]["lobby"] = $this->urlLobby;
        }

        if(! is_null($this->urlCashier)){
            $params["config"]["urls"]["sessionTimeout"] = $this->urlSessionTimeout;
        }

        if(! is_null($this->urlCashier)){
            $params["config"]["urls"]["gameHistory"] = $this->urlGameHistory;
        }

        if(! is_null($this->urlCashier)){
            $params["config"]["urls"]["realityCheckURL"] = $this->urlRealityCheckURL;
        }

        if(!empty($this->vipGroupID)){
            $params['player']['group'] = [
                "id" => $this->vipGroupID,
                "action" => 'assign'
            ];
        }

        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        if(in_array($game_mode, ['trial', 'demo'])){
            $params['demo']['language'] = $language; 
            $params['demo']['currency'] = strtoupper($this->currency);
            $params['config']['game']['playMode'] = "demo";
            $params["config"]["channel"]["wrapped"] = false;
            unset($params["config"]["channel"]["mobile"]);
            // unset($params["config"]["brand"]); // required in demo
            unset($params['player']);
        } 

        $this->CI->utils->debug_log("EVOLUTION SEAMLESS params",$params);

        return Abstract_game_api::callApi(self::API_login, $params, $context);
    }

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh-Hans'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th'; // thai
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
                $lang = 'pt'; // portuquese
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-in':
            case 'hi-IN':
            case 'hi_HI':
                $lang = 'hi'; // hindu
                break;
            case Language_function::INT_LANG_JAPANESE:
            case 'jp':
            case 'ja':
            case 'ja-jp':
            case 'ja-JP':
                $lang = 'ja';
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
	}


    /**
     * Game launch
     *
     * @param string $playerName
     * @param array $extra
     *
     * @return array
     */
    public function queryForwardGame($playerName,$extra=null)
    {
        $result = $this->login($playerName, $extra);
        $success = (isset($result["success"]) && $result['success']) ? true : false;
        $entry = isset($result["entry"]) ? $result["entry"] : null;
        $url = null;

        $this->CI->utils->debug_log('Evolution queryForwardGame extra', $extra);
        
        if( empty($extra['game_code']) && !$this->isSupportsLobby()){ //prevent lobby launch for subproviders. required is_support_lobby : true
            $success = false;
        }
        
        if($success) {

            $url = $this->game_launch_url.$entry;
            return array('success' => $success, 'url' => $url);
        }

        return array('success' => $success, 'url' => $url);
    }

    public function syncOriginalGameLogs($token = false) {
        if($this->use_transaction_data) {
            return $this->returnUnimplemented();
        } else {
            return parent::syncOriginalGameLogs($token);
        }
    }

    public function syncMergeToGameLogs($token = false)
    {
        if($this->use_transaction_data) {

            $enabled_game_logs_unsettle=true;

            return $this->commonSyncMergeToGameLogs($token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTrans'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                    [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                    $enabled_game_logs_unsettle);

        } else {

            parent::syncMergeToGameLogs($token);
        }
    }

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){

        $gameRecords = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time);

        $rebuildGameRecords = array();

        #$this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);

        if(!$this->enable_merging_rows){
            $this->processGameRecordsFromTransUnMerged($gameRecords, $rebuildGameRecords);
        }else{
            $this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);
        }

        return $rebuildGameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo) {
        #query bet only
        $sqlTime="original_table.updated_at >= ? AND original_table.updated_at <= ? AND (original_table.action = 'debit')";
        $uniqueId = "original_table.gameId";
        if(!$this->enable_merging_rows){
            $sqlTime="original_table.updated_at >= ? AND original_table.updated_at <= ?";
            $uniqueId = "original_table.external_uniqueid";
        }

        $md5Fields = implode(", ", array(
                                            'gd.status', 
                                            'original_table.transactionAmount', 
                                            'original_table.gameDetailsTableId', 
                                            'original_table.gameDetailsTableId', 
                                            'gd.id', 
                                            'gd.game_type_id', 
                                        )
                                    );

        $tableName = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT original_table.id as sync_index,
original_table.id,
-- original_table.external_uniqueid,
original_table.gameId as game_round_id,
{$uniqueId} as external_uniqueid,
original_table.created_at as bet_time,
original_table.updated_at as end_time,
original_table.userId as username,
original_table.response_result_id,
original_table.transactionAmount as bet_amount,
original_table.transactionAmount as transaction_amount,
original_table.transactionAmount as real_bet_amount,
-- original_table.player_payout as result_amount,

-- original_table.last_sync_time,
MD5(CONCAT({$md5Fields})) as md5_sum,
original_table.gameDetailsTableId as game_code,
original_table.gameDetailsTableId as game,
-- original_table.participants as participants,
-- original_table.decisions as decisions,
original_table.currency as currency,
original_table.action as action,
original_table.refundedIn as refunded_in,

-- original_table.result as original_result,
-- original_table.participants as original_participants,
original_table.gameType as original_game_type,

original_table.beforeBalance as before_balance,
original_table.afterBalance as after_balance,
original_table.transactionRefId as reference_transaction_id,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as original_table

left JOIN game_description as gd ON original_table.gameDetailsTableId COLLATE utf8_unicode_ci = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON original_table.userId = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE
{$sqlTime} AND original_table.is_processed = ?
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            self::IS_PROCESSED,
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function processGameRecordsFromTrans(&$gameRecords, &$rebuildGameRecords) {
        $this->CI->load->model('original_seamless_wallet_transactions');

        $transaction_ids = array();

        $gameRoundIds = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {


                if (!in_array($record['game_round_id'], $gameRoundIds)) {
                    $after_balance = $this->CI->original_seamless_wallet_transactions->getSpecificColumn($this->getTransactionsTable(), 'afterBalance', "(action != 'debit' AND transactionRefId = '{$record['reference_transaction_id']}')");

                    $temp_game_records = $record;
                    $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                    $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                    $temp_game_records['player_username'] = isset($record['username']) ? $record['username'] : null;

                    $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['round_number'] = isset($record['game_round_id']) ? $record['game_round_id'] : null;
                    $temp_game_records['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                    $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                    // $temp_game_records['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                    $temp_game_records['start_at'] = isset($record['bet_time']) ? $record['bet_time'] : null;
                    $temp_game_records['bet_at'] = isset($record['bet_time']) ? $record['bet_time'] : null;
                    // $temp_game_records['end_at'] = isset($record['bet_time']) ? $record['bet_time'] : null;#default set
                    // $temp_game_records['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null; //

                    if (!empty($after_balance)) {
                        $temp_game_records['after_balance'] = $after_balance;
                    } else {
                        $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                    }

                    // before_balance and after_balance because bet and (result) is in separate transaction.

                    //$temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;

                    //$temp_game_records['bet_amount'] = $record["bet_amount"];
                    //$temp_game_records['real_betting_amount'] = $record["bet_amount"];

                    $bet_results = $this->queryBetResults($record['game_round_id'],$record['username']);

                    $status_type = "";

                    $has_credit = false;

                    if($bet_results) {

                        $temp_game_records['bet_amount'] = $bet_results["bet_amount"];
                        $temp_game_records['real_betting_amount'] = $bet_results["bet_amount"];

                        $temp_game_records['result_amount'] = $bet_results['result_amount'] - $bet_results["bet_amount"];
                        $temp_game_records['end_at'] = $bet_results['end_at'];

                        $status_type = $bet_results['type'];

                        $has_credit = $bet_results['has_credit'];

                    } else {
                        $temp_game_records['result_amount'] = isset($record['bet_amount']) ? -$record['bet_amount'] : null;
                    }

                    $temp_game_records['bet_details'] = isset($record['bet_details']) ? $record['bet_details'] : null;

                    // if($status_type == "credit") {
                    //     $temp_game_records['status'] = Game_logs::STATUS_SETTLED;
                    // } else if ($status_type == "cancel") {
                    //     $temp_game_records['status'] = Game_logs::STATUS_REFUND;
                    // } else {
                    //     $temp_game_records['status'] = Game_logs::STATUS_PENDING;
                    // }

                    if($has_credit) {
                        $temp_game_records['status'] = Game_logs::STATUS_SETTLED;
                    } else {
                        $temp_game_records['status'] = Game_logs::STATUS_PENDING;
                    }

                    if(!isset($temp_game_records['end_at'])){
                        $temp_game_records['end_at'] = isset($record['bet_time']) ? $record['bet_time'] : null;#default set
                    }
                    $temp_game_records['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($temp_game_records, ['status', 'end_at', 'bet_amount', 'result_amount'], ['bet_amount', 'result_amount']);
                    // $gameRecords[$index] = $temp_game_records;
                    $rebuildGameRecords[] = $temp_game_records;
                    $gameRoundIds[] = $record['game_round_id'];
                    unset($data);
                }
            }

        }

    }

    public function processGameRecordsFromTransUnmerged(&$gameRecords, &$rebuildGameRecords) {

        $transaction_ids = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {
                $temp_game_records = $record;
                $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                $temp_game_records['player_username'] = isset($record['username']) ? $record['username'] : null;

                $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                $temp_game_records['round_number'] = isset($record['game_round_id']) ? $record['game_round_id'] : null;
                $temp_game_records['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;

                $temp_game_records['start_at'] = isset($record['bet_time']) ? $record['bet_time'] : null;
                $temp_game_records['bet_at'] = isset($record['bet_time']) ? $record['bet_time'] : null;
                $temp_game_records['end_at'] = isset($record['end_time']) ? $record['end_time'] : null;

                $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;

                $temp_game_records['bet_amount'] = 0;
                $temp_game_records['real_betting_amount'] = 0;
                $temp_game_records['result_amount'] = 0;

                $temp_game_records['status'] = Game_logs::STATUS_PENDING;

                if($record['action']=='cancel'){
                    continue;
                }elseif($record['action']=='credit'){
                    $temp_game_records['status'] = Game_logs::STATUS_SETTLED;

                    $temp_game_records['bet_amount'] = 0;
                    $temp_game_records['real_betting_amount'] = 0;
                    $temp_game_records['result_amount'] = $record['transaction_amount'];
                }elseif($record['action']=='debit'){
                    $temp_game_records['bet_amount'] = $record['transaction_amount'];
                    $temp_game_records['real_betting_amount'] = $record['transaction_amount'];
                    $temp_game_records['result_amount'] = -1 * $record['transaction_amount'];

                    if(!empty($record['refunded_in'])){
                        $temp_game_records['result_amount'] = 0;
                        $temp_game_records['status'] = Game_logs::STATUS_CANCELLED;
                    }else{
                        $bet_results = $this->queryBetResults($record['game_round_id'],$record['username']);

                        if($bet_results['has_credit']) {
                            $temp_game_records['status'] = Game_logs::STATUS_SETTLED;
                        }
                    }
                }

                $temp_game_records['bet_details'] = isset($record['bet_details']) ? $record['bet_details'] : null;
                $max_length_of_unique_id = 64;
                if(strlen($temp_game_records['external_uniqueid']) > $max_length_of_unique_id){
                    $temp_game_records['external_uniqueid'] = substr($temp_game_records['external_uniqueid'], 0, $max_length_of_unique_id);
                }

                $rebuildGameRecords[] = $temp_game_records;

            }

        }

    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row){
        if(empty($row['md5_sum'])){            
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        // $winAmount = null;
        // if($this->enable_merging_rows){
        //     $winAmount = $row['result_amount'] + $row['bet_amount'];
        // }
        
        $data = [
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
                'after_balance' => $row['after_balance']
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
            'bet_details' => $this->preprocessBetDetails($row),
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row){
        if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$this->getUnknownGame());
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['bet_amount'] = $this->gameAmountToDB($row['bet_amount']);
        $row['result_amount'] = $this->gameAmountToDB($row['result_amount']);
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['bet_amount'], $row['result_amount'])) {
            $bet_details['win_amount'] = $row['result_amount'] + $row['bet_amount'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (!$this->enable_merging_rows) {
            $bet_results = $this->queryBetResults($row['round_number'], $row['player_username'], $row['reference_transaction_id']);

            if ($row['action'] == self::ACTION_DEBIT) {
                // get win amount
                $bet_details['win_amount'] = $bet_results['result_amount'];
            }

            if ($row['action'] == self::ACTION_CREDIT) {
                // get bet amount
                $bet_details['bet_amount'] = $bet_results['bet_amount'];
            }

            if ($row['status'] == Game_logs::STATUS_REFUND) {
                // get bet amount
                $bet_details['bet_amount'] = $bet_results['bet_amount'];

                $bet_details['refund_amount'] = $bet_results['bet_amount'];

                unset($bet_details['win_amount']);
            }
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function queryBetResults($round_id, $player_name, $reference_transaction_id = null) {

            //$sqlTime="evolution.gameId = ? and evolution.userId = ? and evolution.action != 'debit'";
            $sqlTime="evolution.gameId = ? and evolution.userId = ? and evolution.action != 'cancel'";
            $and_reference_transaction_id = !empty($reference_transaction_id) ? "AND evolution.transactionRefId = ?" : '';

            $tableName = $this->getTransactionsTable();
            
            $sql = <<<EOD
SELECT
evolution.id as sync_index,
evolution.transactionAmount as amount,
evolution.created_at as end_at,
evolution.afterBalance as after_balance,
evolution.action as transaction_type,
evolution.refundedIn
FROM {$tableName} as evolution
WHERE
{$sqlTime} AND evolution.is_processed = ? {$and_reference_transaction_id}
EOD;

            $params=[
                $round_id,
                $player_name,
                self::IS_PROCESSED,
            ];

            if (!empty($reference_transaction_id)) {
                array_push($params, $reference_transaction_id);
            }

            $this->CI->utils->debug_log('merge sql', $sql, $params);

            $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);


            return $this->processResultAmount($result);
    }

    private function processResultAmount($datas) {

        $result = false;

        if(!empty($datas)){


            $total_result_amount = 0;
            $total_bet_amount = 0;
            $end_at = null;
            $type = "";
            $hasCredit = false;

            foreach($datas as $data) {

                if($data["transaction_type"] == "debit") {

                    if(empty($data["refundedIn"])) {
                        $total_bet_amount += $data["amount"];
                    }

                } else if ($data["transaction_type"] == "credit") {

                    $total_result_amount += $data["amount"];
                    $hasCredit = true;
                    $end_at = $data["end_at"];
                }
                $type = $data["transaction_type"];
            }

            $result = array(
                                "bet_amount"    => $total_bet_amount,
                                "result_amount" => $total_result_amount,
                                "end_at" => $end_at,
                                "type" => $type,
                                "has_credit" => $hasCredit
                            );



        }
        return $result;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $row['game_code']);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function triggerInternalPayoutRound($params){
        $params = json_decode($params, true);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
        ];
        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_triggerInternalPayoutRound, $params, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);
        $success = isset($resultArr['status']) &&  strtolower($resultArr['status']) == "ok" ? true : false; 
        $result = ["message" => json_encode($resultArr)];
        return [$success, $result];
    }

    public function encrypt($data){
        if(is_array($data)){
            $data = json_encode($data);
        }
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_encrypt($data, $this->encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function decrypt($data){
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($data), $this->encrypt_method, $key, 0, $iv);
        return $output;
    }

    public function generateSessionId($params = []){
        #$str = json_encode($params);
        #$token = $this->encrypt($str);
        $this->CI->utils->debug_log("EVOLUTION SEAMLESS generateSessionId ", 'params',$params);
          
        $token = '';

        if(isset($params['currency'])){
            $token .= $params['currency'];
        }

        if(isset($params['token'])){
            if(!empty($token)){
                $token .= '|';
            }
            $token .= $params['token'];
        }

        if(isset($params['player_username'])){
            if(!empty($token)){
                $token .= '|';
            }
            $token .= $params['player_username'];
        }

        if(isset($params['player_gameusername'])){
            if(!empty($token)){
                $token .= '|';
            }
            $token .= $params['player_gameusername'];
        }

        if(isset($params['game_platform_id'])){
            if(!empty($token)){
                $token .= '|';
            }
            $token .= $params['game_platform_id'];
        }

        $this->CI->utils->debug_log("EVOLUTION SEAMLESS generateSessionId ", 'token',$token);
        return $token;
    }

    public function queryGameListFromGameProvider($extra = null) {
        $params = [
            'gameVertical' => $this->game_vertical,
            'gameProvider' => $this->game_provider,
        ];
    
        $this->method = self::METHOD_GET;
        $this->currentMethod = self::API_queryGameListFromGameProvider;
        $url = $this->generateUrl($this->currentMethod, $params);
    
        list($response, $httpCode) = $this->customHttpCall2($url, "{$this->casino_key}:{$this->external_lobby_api_token}");
    
        $resultArr = json_decode($response, true);
        $success = ($httpCode >= 200 && $httpCode < 300 && !empty($resultArr));
    
        if ($success) {
            $result['games'] = $this->rebuildGameList($resultArr);
        } else {
            $result['error'] = 'Failed to retrieve game list or invalid response';
        }
    
        return [$success, $result];
    }
    
    private function customHttpCall2($url, $auth) {
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: {$error}");
        }
    
        curl_close($ch);
    
        return [$response, $httpCode];
    }

    protected function rebuildGameList($game=[])
    {
        $data = [];
        if (isset($game['players'])) {
            unset($game['players']);
        }
        if (isset($game['tables'])) {
            foreach ($game['tables'] as $key => $table) {
                if (isset($table['descriptions'])) {
                    unset($table['descriptions']);
                }
                if (isset($table['videoSnapshot'])) {
                    unset($table['videoSnapshot']);
                }
                if (isset($table['dealerHand'])) {
                    unset($table['dealerHand']);
                }
                if (isset($table['seatsTaken'])) {
                    unset($table['seatsTaken']);
                }
                if (isset($table['dealer'])) {
                    unset($table['dealer']);
                }
                if (isset($table['privateTableConfig'])) {
                    unset($table['privateTableConfig']);
                }
                if (isset($table['betLimits'])) {
                    unset($table['betLimits']);
                }
                if (isset($table['operationSchedules'])) {
                    unset($table['operationSchedules']);
                }
                if (isset($table['operationHours'])) {
                    unset($table['operationHours']);
                }
                if (isset($table['sitesAssigned'])) {
                    unset($table['sitesAssigned']);
                }
                if (isset($table['players'])) {
                    unset($table['players']);
                }
                if (isset($table['seats'])) {
                    unset($table['seats']);
                }
                if (isset($table['betBehind'])) {
                    unset($table['betBehind']);
                }
                if (isset($table['seatsLimit'])) {
                    unset($table['seatsLimit']);
                }
                if (isset($table['sitesBlocked'])) {
                    unset($table['sitesBlocked']);
                }
                if (isset($table['results'])) {
                    unset($table['results']);
                }
                if (isset($table['history'])) {
                    unset($table['history']);
                }
                if (isset($table['road'])) {
                    unset($table['road']);
                }
                $game['tables'][$key] = $table; // update the game tables after unsetting
            }
        }
    
        $data[] = $game;
  
        return $data;
    }

    public function getGameProviderGameList() {
        $queryGameList = $this->queryGameListFromGameProvider();
        $gameList = !empty($queryGameList['games']['tables']) ? $queryGameList['games']['tables'] : [];
        $success = !empty($gameList);
        $result = [
            'success' => false,
            'message' => 'no game list',
        ];

        if ($success) {
            $list = [];

            $gameTypeList = array(
                'live' => 'live_dealer',
                'rng' => 'slots',
                'slots' => 'slots',
            );

            $this->CI->load->model(['game_description_model','game_type_model']);
            $dbGameTypeList = $this->getDBGametypeList();

            foreach ($gameList as $key => $gameDetail) {
                $gameTypeCode = isset($gameTypeList[$gameDetail['gameVertical']]) ? $gameTypeList[$gameDetail['gameVertical']] : 'others';
                $gameTypeId = $dbGameTypeList[$gameTypeCode]['id'];
                $game_name = isset($gameDetail['name']) ? $gameDetail['name'] : 'unknown';
                $game_code = isset($gameDetail['tableId']) ? $gameDetail['tableId'] : 'unknown';
                $display = isset($gameDetail['display']) ? $gameDetail['display'] : null;

                $attributes = [
                    'game_type' => $gameDetail['gameTypeUnified'],
                ];

                $lang_arr = [
                    self::INT_LANG_ENGLISH 		=> $game_name,
                    self::INT_LANG_CHINESE 		=> $game_name,
                    self::INT_LANG_INDONESIAN   => $game_name,
                    self::INT_LANG_VIETNAMESE   => $game_name,
                    self::INT_LANG_KOREAN 		=> $game_name,
                ];

                $list[$key] = [
                    'game_platform_id' 	 => $this->getPlatformCode(),
                    'game_type_id' 	  	 => $gameTypeId,
                    'game_code' 	 	 => $game_code,
                    'attributes' 	 	 => json_encode($attributes),
                    'english_name' 		 => $game_name,
                    'external_game_id' 	 => $game_code,
                    'enabled_freespin' 	 => Game_description_model::DB_FALSE,
                    'sub_game_provider'  => null,
                    'enabled_on_android' => $this->checkGameAttribute('mobile', $display),
                    'enabled_on_ios' 	 => $this->checkGameAttribute('mobile', $display),
                    'status' 			 => Game_description_model::DB_TRUE,
                    'flash_enabled' 	 => Game_description_model::DB_FALSE,
                    'mobile_enabled' 	 => $this->checkGameAttribute('mobile', $display),
                    'html_five_enabled'  => $this->checkGameAttribute('desktop', $display),
                    'game_name' 		 => $this->processLanguagesToJson($lang_arr),
                ];
            }

            $result = $this->CI->game_description_model->syncGameDescription($list, null, false, true, null, $this->getGameListAPIConfig());
        }

        return $result;
    }

    public function checkGameAttribute ($key, $data) {
        return (strpos($data, $key) !== false) ? Game_description_model::DB_TRUE : Game_description_model::DB_FALSE;
    }

    public function getStatusBasedOnAction($action)
    {
        switch ($action) {
            case self::ACTION_DEBIT:
                return Game_logs::STATUS_PENDING;
            case self::ACTION_CREDIT:
                return Game_logs::STATUS_SETTLED;
            case self::ACTION_CANCEL:
                return Game_logs::STATUS_CANCELLED;
            default:
                return Game_logs::STATUS_PENDING;
        }
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $action = self::ACTION_DEBIT;
        $pendingRowCount = 1;
        // Get all transaction where count is only 1 row as per round_id where action=debit / bet - means missing payout/settlement
        // Use join instead of subquery to avoid slow execution

        $sql = <<<EOD
SELECT 
original.transactionRefId as round_id, original.transactionId as transaction_id, game_platform_id
from {$this->original_transactions_table} as original
JOIN (
    SELECT transactionRefId
    FROM {$this->original_transactions_table}
    GROUP BY transactionRefId
    HAVING COUNT(id) = ?
) as filtered on filtered.transactionRefId = original.transactionRefId
where 1
AND action = ?
and {$sqlTime}
EOD;

        $params=[
            $pendingRowCount,
            $action,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('EVOLUTION SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $this->original_transactions_table = $this->getTransactionsTable();

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.action as transaction_type,
original.game_platform_id,
original.player_id,
original.transactionRefId as round_id,
original.transactionId as transaction_id,
ABS(SUM(original.transactionAmount)) as amount,
ABS(SUM(original.transactionAmount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_uniqueid
from {$this->original_transactions_table} as original
left JOIN game_description as gd ON original.gameId = gd.external_game_id and gd.game_platform_id=?
where
transactionRefId=? and transactionId=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach ($transactions as $transaction) {
            if (!$transaction['game_platform_id'] || $this->CI->seamless_missing_payout->checkIfAlreadyExist($transaction['external_uniqueid'])) {
                continue;
            }

            $transaction['transaction_status'] = $this->getStatusBasedOnAction($transaction['transaction_type']);
            $transaction['added_amount'] = $baseAmount;
            $transaction['status'] = Seamless_missing_payout::NOT_FIXED;
            $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $transaction);
        
            if ($result === false) {
                $this->CI->utils->error_log('JILI SEAMLESS-' . $this->getPlatformCode() . '(checkBetStatus) Error insert missing payout', $transaction);
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
count(id) as row_count
FROM {$this->original_transactions_table} as original
JOIN (
	SELECT 
	transactionRefId
	FROM {$this->original_transactions_table}
	WHERE external_uniqueid=? and game_platform_id=?
) as filtered on filtered.transactionRefId=original.transactionRefId
WHERE 1
and original.game_platform_id=?
EOD;
     
        $params=[$external_uniqueid, $game_platform_id, $game_platform_id];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if (!empty($trans['row_count']) && $trans['row_count'] > 1) {
            return ['success' => true, 'status' => Game_logs::STATUS_SETTLED];
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }

    protected function saveResponseResult($success, $apiName, $params, $resultText, $statusCode, $statusText = null,
        $extra = null, $field = null, $dont_save_response_in_api = false, $costMs=null) {
        
        if(!empty($extra)){
            $extra = json_decode($extra);
            $extra[]['header'] = $this->getHttpHeaders([]);
            $extra = json_encode($extra);
        }
        //save to db
        $this->CI->load->model("response_result");
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($field==null){
            $field=[];
        }
        //try add decoded_result_text
        $decoded_result_text=$this->getDecodedResultText($resultText, $apiName, $params, $statusCode);
        if(!empty($decoded_result_text)){
            $field['decoded_result_text']=$decoded_result_text;
        }

        return $this->CI->response_result->saveResponseResult($this->SYSTEM_TYPE_ID, $flag, $apiName, json_encode($params), $resultText, $statusCode, $statusText,
            $extra, $field, $dont_save_response_in_api, null, $costMs, $this->transfer_request_id, $this->_proxySettings);
    }
}