<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_oneapi_seamless extends Abstract_game_api {

    public  $URI_MAP, 
            $METHOD_MAP, 
            $url, 
            $method, 
            $currency, 
            $language, 
            $force_lang ,
            $enable_merging_rows,
            $use_monthly_transactions_table,
            $original_transactions_table,
            $prefix_for_username,
            $subprovider_username_prefix,
            $game_platform_id,
            $home_url,
            $api_key,
            $api_secret,
            $fix_username_limit,
            $minimum_user_length,
            $maximum_user_length,
            $default_fix_name_length,
            $enable_hint,
            $use_truncate_decimal_amount,
            $conversion_precision,
            $compute_total_win_by_betid_and_roundid,
            $allow_group_by_merging,
            $vendor_code,
            $use_new_sync_merge
           ;


    const POST                  = 'POST';
    const GET                   = 'GET';

    const MD5_FIELDS_FOR_MERGE = [
        'status'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'bet_amount',
        'win_amount',
        'after_balance'
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','game_description_model','external_common_tokens'));

        $this->URI_MAP = array(
            self::API_queryForwardGame  => '/game/url',
            self::API_queryDemoGame     => '/game/url',
            self::API_queryGameListFromGameProvider     => '/game/list',
        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame  => self::POST,                    
            self::API_queryDemoGame     => self::POST,      
            self::API_queryGameListFromGameProvider => self::POST,                
        ); 

        $this->original_transactions_table = 'oneapi_seamless_wallet_transactions';

        $this->url                    = $this->getSystemInfo('url',null);
        $this->currency               = $this->getSystemInfo('currency',null);
        $this->language               = $this->getSystemInfo('language',null);
        $this->force_lang             = $this->getSystemInfo('force_lang', false);
        $this->enable_merging_rows    = $this->getSystemInfo('enable_merging_rows', true);
        $this->home_url               = $this->getSystemInfo('home_url', false);
        $this->api_key                = $this->getSystemInfo('api_key', false);
        $this->api_secret             = $this->getSystemInfo('api_secret', false);
        $this->prefix_for_username    = $this->getSystemInfo('prefix_for_username','');
        $this->fix_username_limit       = $this->getSystemInfo('fix_username_limit',true);
        $this->minimum_user_length      = $this->getSystemInfo('minimum_user_length',5);
        $this->maximum_user_length      = $this->getSystemInfo('maximum_user_length',20);
        $this->default_fix_name_length  = $this->getSystemInfo('default_fix_name_length',20);
        $this->enable_hint  = $this->getSystemInfo('enable_hint',false);
        $this->use_truncate_decimal_amount = $this->getSystemInfo('use_truncate_decimal_amount',true);
        $this->conversion_precision = $this->getSystemInfo('conversion_precision',3);
        $this->compute_total_win_by_betid_and_roundid = $this->getSystemInfo('compute_total_win_by_betid_and_roundid',false);
        $this->allow_group_by_merging = $this->getSystemInfo('allow_group_by_merging',false);
        $this->vendor_code = $this->getSystemInfo('vendor_code',null);
        $this->use_new_sync_merge   = $this->getSystemInfo('use_new_sync_merge',false);
        
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode(){
        return $this->game_platform_id;
    }

    public function getCurrency(){
        return $this->currency;
    }
    
    public function queryForwardGame($playerName, $extra = null)
    {   
        $this->utils->debug_log("ONEAPI SEAMLESS: (queryForwardGame)", $extra);
        $language = isset($this->language) ? $this->language : $extra['language'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        ];

        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo')){
            #demo
            $apiName = self::API_queryDemoGame;
            $params = [
                
            ];
        }else{
            #real
            $gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
            $apiName        = self::API_queryForwardGame;

            $context['playerName'] = $playerName;
            $context['gameUsername'] = $gameUsername;

            $params = [
                "username"  => $gameUsername,
                "traceId"   => $this->generateTraceID(),
                "gameCode"  => $extra['game_code'],
                "language"  => $this->getLauncherLanguage($language),
                "platform"  => $extra['is_mobile'] ? 'H5' : 'WEB',
                "currency"  => $this->currency,
                "ipAddress" => $this->CI->utils->getIP()
            ];

            if(isset($this->home_url) && !empty($this->home_url)){
                $params['lobbyUrl'] = $this->home_url;
            }
        }

        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $resultArr          = $this->getResultJsonFromParams($params);
        $responseResultId   = $this->getResponseResultIdFromParams($params);
        $gameUsername       = @$this->getVariableFromContext($params, 'gameUsername');
		$success            = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result             = [];

        $this->utils->debug_log("ONEAPI SEAMLESS: (processResultForQueryForwardGame)", $resultArr);
        $result['url'] = '';
        if($success){
            $result['url'] = $resultArr['data']['gameUrl'];
        }
        return array(true, $result);
    }

    public function generateTraceID(){
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $UUID = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return $UUID;
    }

    public function generateSignature($params){
        if(is_array($params)){
            $json = json_encode($params);
        }else{
            $json = $params;
        }
        $signature = hash_hmac('sha256', $json, $this->api_secret);
        return $signature;
    }

    public function queryGameListFromGameProvider($extra = []) {
        $get_request = $this->CI->input->get();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];
        
        $params = [
            "traceId"   => $this->generateTraceID(),
            "vendorCode" => $this->vendor_code,
            "pageNo" => 1
        ];

        if(!empty($get_request['page_number'])){
            $params['pageNo'] = $get_request['page_number'];
        }

        if(!empty($get_request['page_size'])){
            $params['pageSize'] = $get_request['page_size'];
        }

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $result = [];

        if ($success) {
            $result = !empty($result_arr) ? $result_arr : [];
        }

        return array($success, $result);
    }
   
    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
        
		$success = false;
		if(isset($resultArr['status']) && $resultArr['status'] == 'SC_OK'){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ONEAPI got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}
    
    protected function customHttpCall($ch, $params)
    {
        if ($this->method == self::POST) {
            $header = [
                'Content-Type: application/json',
                'x-api-key: '.$this->api_key,
                'x-signature: '.$this->generateSignature($params)
            ];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("ONEAPI SEAMLESS: (createPlayer)");
        // $prefix = $this->game_platform_id.$this->prefix_for_username;
        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for ONEAPI seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for ONEAPI seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    // public function convertUsernameToGame($username) {
    //     $prefix = $this->game_platform_id.$this->prefix_for_username;
    //     if (!empty($prefix)) {
    //         $username = $prefix . $username;
    //     }
    //     return $username;
    // }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
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
        $original_transactions_table = $this->getTransactionsTable();
        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time); 
        $this->CI->utils->debug_log("ONEAPI SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        if($this->use_new_sync_merge){
            $results = [];
            $start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
                $monthlyResults = $this->new_queryOriginalGameLogsWithTable($tableName, $dateFrom, $dateTo, $use_bet_time);
                $results = array_merge($results, $monthlyResults);
            }

            $results = array_values($results);
            return $results;
        }
        $this->utils->debug_log('ONEAPI-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time){
            $sqlTime='`original`.`bet_time` >= ? AND `original`.`bet_time` <= ?';
        }

        $trans_type = "AND original.trans_type = 'bet'";
        $groupBy = '';
        $allow_group_by_api = [
            ONEAPI_BGAMING_SEAMLESS_GAME_API, 
            ONEAPI_SPRIBE_SEAMLESS_GAME_API,
            ONEAPI_JDB_SEAMLESS_GAME_API, 
            ONEAPI_JDBGTF_SEAMLESS_GAME_API,
            ONEAPI_FACHAI_SEAMLESS_GAME_API,
            ONEAPI_ILOVEU_SEAMLESS_GAME_API,
            ONEAPI_BNG_SEAMLESS_GAME_API
        ];

        #used for oneapi methods that used '/bet_result' only
        if($this->enable_merging_rows &&
            (in_array($this->game_platform_id,$allow_group_by_api) || $this->allow_group_by_merging))
        {
            $trans_type = '';
            $groupBy = "group by original.round_id, original.player_id";
        }

        if(!$this->enable_merging_rows){
            $trans_type = "AND (original.result_type = 'BET_WIN' OR original.result_type = 'BET_LOSE' OR original.result_type ='WIN' OR original.result_type is NULL)";
        }
      
        $this->CI->utils->debug_log('ONEAPI SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'COALESCE(original.win_amount, 0)' , 'original.after_balance', 'original.status'));
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.transaction_id as transaction_id,
    original.round_id as round_id,
    original.bet_id,
    original.amount,
    original.bet_amount,
    original.win_amount,
    original.winloss,
    original.game_code,
    original.currency,
    original.trans_type,
    original.result_type,
    original.status,
    original.transaction_date,
    original.bet_time,
    original.settled_time,
    original.extra_info,

    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,

    gpa.login_name as player_username,

    MD5(CONCAT({$md5Fields})) as md5_sum

FROM {$table} as original
LEFT JOIN game_provider_auth as gpa ON original.player_id = gpa.player_id   
AND gpa.game_provider_id = ?
WHERE 
original.game_platform_id = ? 
{$trans_type} AND 
{$sqlTime}
{$groupBy};
EOD;
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('ONEAPI-syncSQL', $sql, 'params',$params);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('ONEAPI SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        // print_r($row['md5_sum'].'-'.$row['external_uniqueid']); 

        
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $this->gameTimeToServerTime($row['start_at']),
                'end_at'                => $this->gameTimeToServerTime($row['end_at']),
                'bet_at'                => $this->gameTimeToServerTime($row['start_at']),
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null,
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('ONEAPI ', $data);
        return $data;

    }

     /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        $game_code =  $row['game_code'];
        $game_desc = $this->CI->game_description_model->getGameDescByGameCode($game_code, $this->getPlatformCode());
        if (empty($game_desc)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
            $row['game_description_id'] = $game_description_id;
            $row['game_type_id'] = $game_type_id;
            $row['game_name'] = 'unknown';
        }else{
            $row['game_description_id'] = $game_desc['id'];
            $row['game_type_id'] = $game_desc['game_type_id'];
            $row['game_name'] = $game_desc['game_name'];
        }
   
        if($this->enable_merging_rows){ 
            $row['after_balance']   = $row['after_balance'] + $row['win_amount'];
            #get total win amounts including free spins
            $totalAmounts = $this->queryTotalAmountByRound($row);
            $row['win_amount'] = isset($totalAmounts['total_win']) ? $totalAmounts['total_win'] : 0;
            $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];

            if (isset($totalAmounts['after_balance'])) {
                $row['after_balance'] = $totalAmounts['after_balance'];
            }

        }else{
            if($row['trans_type'] == 'bet' || ($row['trans_type'] == 'bet_result' && in_array($row['result_type'], ['BET',null,'BET_WIN']))){
                $row['win_amount'] = 0; 
            }else{
                $row['bet_amount'] = 0;
            }

            $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
        }


        $row['start_at'] = isset($row['bet_time']) ? $row['bet_time'] : $row['transaction_date'];
        $row['end_at'] = isset($row['settled_time']) ? $row['settled_time'] : $row['transaction_date'];

        #OGDATA-1312
        #double check if already have settlement request
        if($row['status'] == Game_logs::STATUS_PENDING){
            $table = $this->getTransactionsTable();
            $queryBetStatus = $this->queryBetStatus($table, $row['round_id'], $row['player_id']);
            if($queryBetStatus['status']){
                $row['status'] = $queryBetStatus['status'];
            }
        }
    }


    public function new_queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime="original.updated_at >= ? AND original.updated_at <= ?";
        $md5Fields = implode(", ", array('original.bet_amount', 'COALESCE(original.win_amount, 0)' , 'original.after_balance', 'original.status','original.updated_at'));

        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.transaction_id as transaction_id,
    original.round_id as round_id,
    original.bet_id,
    original.amount,
    original.bet_amount,
    original.win_amount,
    original.winloss,
    original.game_code,
    original.currency,
    original.trans_type,
    original.result_type,
    original.status,
    original.transaction_date,
    original.bet_time,
    original.settled_time,
    original.extra_info,

    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,

    gpa.login_name as player_username,

    MD5(CONCAT({$md5Fields})) as md5_sum

FROM {$table} as original
LEFT JOIN game_provider_auth as gpa ON original.player_id = gpa.player_id   
AND gpa.game_provider_id = ?
WHERE 
original.game_platform_id = ? 
AND (original.result_type != 'END' OR original.trans_type = 'bet') AND
{$sqlTime};
EOD;
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

        $this->CI->utils->debug_log('merge sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($result as $key => $value){
            if($value['trans_type'] == 'bet_result'){
                #for bet_result only, check if it has related /bet transaction
                $queryBetTransaction = $this->queryBetTransaction($table, $value['round_id'], $value['player_id']);
                $is_have_bet_transaction = isset($queryBetTransaction['count']) && $queryBetTransaction['count'] ? true : false;
                if($is_have_bet_transaction){
                    #ignored merging if bet transaction is found for not to be duplicated
                    unset($result[$key]); 
                }
            }
        }

        $result = array_values($result);

        return $result;
    }


    public function queryBetStatus($table_name, $round_id, $player_id){
        $sqlRound = "original.round_id=? AND original.player_id=? AND original.game_platform_id=? 
        AND original.result_type IN ('BET_WIN', 'BET_LOSE', 'LOSE', 'WIN');";

        $sql = <<<EOD
SELECT
original.status

FROM {$table_name} as original
WHERE
{$sqlRound}
EOD;
        $params=[
            $round_id,
            $player_id,
            $this->game_platform_id
        ];

        $this->CI->utils->debug_log('queryBetStatus sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;
    }

    public function queryTotalAmountByRound($data){
            $yearMonthStr =  (new DateTime($data['updated_at']))->format("Ym");
            $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
            $round_id   = isset($data['round_id']) ? $data['round_id'] : null;
            $player_id  = isset($data['player_id']) ? $data['player_id'] : null;
            $bet_id  = isset($data['bet_id']) ? $data['bet_id'] : null;

            $this->CI->load->model('original_game_logs_model');

            $sqlRound="original.round_id = ? AND original.player_id = ? AND original.game_platform_id = ? AND original.trans_type = ? 
            AND (original.result_type = ? OR original.result_type = ?)";

            if($this->compute_total_win_by_betid_and_roundid){
                $sqlRound="original.round_id = ? AND original.bet_id = ? AND original.player_id = ? AND original.game_platform_id = ? AND original.trans_type = ? 
                AND (original.result_type = ? OR original.result_type = ?)";
            }


        $sql = <<<EOD
SELECT
sum(original.win_amount) as total_win,
sum(original.winloss) as total_winloss,
original.after_balance

FROM {$tableName} as original
WHERE
{$sqlRound}
EOD;
        $params=[
            $round_id,
            $player_id,
            $this->game_platform_id,
            'bet_result',
            'BET_WIN',
            'WIN'
        ];

        if($this->compute_total_win_by_betid_and_roundid){
            $params=[
                $round_id,
                $bet_id,
                $player_id,
                $this->game_platform_id,
                'bet_result',
                'BET_WIN',
                'WIN'
            ];
        }

        $this->CI->utils->debug_log('queryTotalBetAmountByRound sql', $sql, $params);
        return $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
    }

    public function queryBetTransaction($table_name, $round_id, $player_id){
        $sqlRound = "original.round_id=? AND original.player_id=? AND original.game_platform_id=? AND original.trans_type=?";

        $sql = <<<EOD
SELECT
count(original.id) as count

FROM {$table_name} as original
WHERE
{$sqlRound}
EOD;
        $params=[
            $round_id,
            $player_id,
            $this->game_platform_id,
            'bet',
        ];

        $this->CI->utils->debug_log('queryBetStatus sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;
    }

    public function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function formatBetDetails($details){
        $bet_details = [];
        if($details){
            $bet_details = [
                'bet_id'                => $details['transaction_id'],
                'action'                => $details['trans_type'],
                'bet_amount'            => $details['bet_amount'],
                'win_amount'            => $details['win_amount'],
                'settlement_datetime'   => $details['updated_at'],
            ];
        }
        return $bet_details;
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$transTable = $this->getTransactionsTable();

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.updated_at as transaction_date,
t.balance_adjustment_amount as amount,
t.after_balance,
t.before_balance,
t.transaction_id as transaction_id,
t.round_id as round_id,
t.external_uniqueid as external_uniqueid,
t.trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount,  
t.extra_info as extra_info
FROM {$transTable} as t
WHERE (t.result_type is NULL OR t.result_type = 'BET_WIN' OR t.result_type = 'WIN' OR t.result_type = 'BET_LOSE' OR t.result_type = 'LOSE') AND  
t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('ONEAPI SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record                       = [];
                $temp_game_record['player_id']          = $transaction['player_id'];
                $temp_game_record['game_platform_id']   = $this->getPlatformCode();
                $temp_game_record['transaction_date']   = $transaction['transaction_date'];
                $temp_game_record['amount']             = abs($transaction['amount']);
                $temp_game_record['before_balance']     = $transaction['before_balance'];
                $temp_game_record['after_balance']      = $transaction['after_balance'];
                $temp_game_record['round_no']           = $transaction['round_id'];
                $extra                                  = [];
                $extra['trans_type']                    = $transaction['trans_type'];
                $temp_game_record['extra_info']         = json_encode($extra);
                $temp_game_record['external_uniqueid']  = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type']  = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['after_balance']<$transaction['before_balance']){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function generateUrl($apiName,$params){
        $this->CI->utils->debug_log('ONEAPI SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

		$this->method   = $this->METHOD_MAP[$apiName];
        if(!empty($params)){
            $url = $url.'?'. http_build_query($params);
        }
		$this->CI->utils->debug_log('ONEAPI SEAMLESS (generateUrl) :', $this->method, $url);

		return $url;
    }

    public function getPlayerBalanceById($player_id){
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());
        return $balance;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){    
            return $this->original_transactions_table;
        }
        $date=new DateTime();
        $monthStr=$date->format('Ym');
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }
        
		$tableName=$this->original_transactions_table.'_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like '.$this->original_transactions_table);

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}
		return $tableName;
	}

    public function getLauncherLanguage($language){
        $language = strtolower($language);
        $lang='';
        switch ($language) {
            case 'en':
            case 'en-us':
            case 'en-US':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en';
                break;
            case 'ph':
            case 'ph-PH':
                $lang = 'tl';
                break;
            case 'pt':
            case 'pt-br':
            case 'pt-BR':
            case 'pt-pt':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'pt';
                break;
            case 'hi':
            case 'hi-hi':
            case 'hi-in':
            case 'hi-IN':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'hi';
                break;
            default: 
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $transTable = $this->getTransactionsTable(); 
        $pendingStatus = Game_logs::STATUS_PENDING;
        $transType = 'bet';

        $sql = <<<EOD
SELECT 
original.round_id as round_id, original.transaction_id, game_platform_id
from {$transTable} as original
where
original.status=?
and original.trans_type=?
and {$sqlTime}
EOD;

        $params=[
            $pendingStatus,
            $transType,
            $dateFrom,
            $dateTo
		];

        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('ONEAPI SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $transTable = $this->getTransactionsTable(); 

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.trans_type as transaction_type,
original.status as status,
original.game_platform_id,
original.player_id,
original.round_id as round_id,
original.transaction_id,
ABS(SUM(original.bet_amount)) as amount,
ABS(SUM(original.bet_amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_uniqueid
from {$transTable} as original
left JOIN game_description as gd ON original.game_code = gd.external_game_id and gd.game_platform_id=?
where
round_id=? and transaction_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = Game_logs::STATUS_PENDING;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('ONEAPI SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $transTable = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
original.status as status
FROM {$transTable} as original
WHERE
original.game_platform_id=? AND original.external_uniqueid=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$trans['status']);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }
}  
/*end of file*/