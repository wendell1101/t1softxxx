<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * PEGASUS Seamless Integration
 * OGP-32673
 * ? use pegasus_seamless__service_api for service API
 * 
 * Game Platform ID: 6374
 *
 */

abstract class Abstract_game_api_common_pegasus_seamless extends Abstract_game_api {

    public  $URI_MAP, 
            $METHOD_MAP, 
            $url, 
            $method, 
            $currency, 
            $language, 
            $force_lang ,
            $merchant_id,
            $secret_key,
            $enable_merging_rows,
            $x_pgs_identity,
            $allow_postman_generate_external_token
           ;

    const POST                  = 'POST';
    const GET                   = 'GET';
    const API_SUCCESS           =  0;

    const MD5_FIELDS_FOR_MERGE = [
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','game_description_model','external_common_tokens'));

        $this->URI_MAP = array(
            self::API_queryForwardGame  => '/game/launch-link',
            self::API_queryDemoGame     => '/game/launch-link',
        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame => self::GET,                    
            self::API_queryDemoGame    => self::GET,                    
        ); 

        $this->url                    = $this->getSystemInfo('url',null);
        $this->currency               = $this->getSystemInfo('currency',null);
        $this->language               = $this->getSystemInfo('language',null);
        $this->force_lang             = $this->getSystemInfo('force_lang', false);
        $this->merchant_id            = $this->getSystemInfo('merchant_id', false);
        $this->secret_key             = $this->getSystemInfo('secret_key', false);
        $this->enable_merging_rows    = $this->getSystemInfo('enable_merging_rows', false);
        $this->x_pgs_identity         = $this->getSystemInfo('x_pgs_identity', false);
        $this->allow_postman_generate_external_token         = $this->getSystemInfo('allow_postman_generate_external_token', false);
       
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return PEGASUS_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }
    
    public function queryForwardGame($playerName, $extra = null)
    {   
        $this->CI->load->model(['external_common_tokens']);
        $this->utils->debug_log("PEGASUS SEAMLESS: (queryForwardGame)", $extra);
        $language       = $this->getLauncherLanguage(!empty($this->language) ? $this->language : $extra['language']);
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame'
        );

        $params = [
            'merchantID'        => $this->merchant_id,
            'merchantSecretKey' => $this->secret_key,
            'gameId'            => isset($extra['game_code']) && $extra['game_code'] != "" ? $extra['game_code'] : null,
        ];

        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo')){
            #demo
            $apiName  = self::API_queryDemoGame;

            $params['isTrialModeV2']    = true;
            $params['playerUniqueID']   = 'demo-playerUniqueID';
            $params['sessionToken']     = 'demo-sessionToken';
        }else{
            #real
            $apiName        = self::API_queryForwardGame;

            $player         = $this->CI->player_model->getPlayerByUsername($playerName);
            $gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
            $player_id      = $player->playerId;
            $player_token   = $this->getPlayerToken($player_id);
            #set external common token (need to be changed every launch)
            $this->CI->external_common_tokens->addPlayerToken($player_id, md5($player_token . time()),  $this->getPlatformCode());
            $sessionToken = $this->CI->external_common_tokens->getPlayerActiveExternalTokens($player_id, $this->getPlatformCode());

            $params['playerUniqueID']   = $gameUsername;
            $params['sessionToken']     = isset($sessionToken[0]) ? $sessionToken[0] : null;

            $context['playerName']      = $playerName;
            $context['gameUsername']    = $gameUsername;
        }

        if($language){
            $params['lang'] = $language;
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
        $this->utils->debug_log("PEGASUS SEAMLESS: (processResultForQueryForwardGame)", $resultArr);
        if($success){
            $result['url'] = $resultArr['data']['link'];
        }
        return array(true, $result);
    }


    


    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("PEGASUS SEAMLESS: (createPlayer)");

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for PEGASUS seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for PEGASUS seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

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
        $this->CI->utils->debug_log("PEGASUS SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('PEGASUS-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time){
            $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        }

        $trans_type = "original.trans_type in ('PlaceBet')";
        if(!$this->enable_merging_rows){
            $trans_type = "original.trans_type in ('PlaceBet','Settle')";
        }
      
        $this->CI->utils->debug_log('PEGASUS SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.payout_amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.transaction_id,
    original.round_id,
    original.bet_amount,
    original.payout_amount,
    original.settlement_time,
    original.game_id as game_code,
    original.currency,
    original.trans_type,
    original.status,
    original.extra_info,
    original.player_unique_id as player_username,

    original.request,
    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,

    MD5(CONCAT({$md5Fields})) as md5_sum

FROM {$table} as original
WHERE 
{$trans_type} AND 
{$sqlTime};
EOD;
        $params=[
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('PEGASUS-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('PEGASUS SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }
        $result_amount = $row['payout_amount'] - $row['bet_amount'];
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
                'result_amount'         => $result_amount,
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['created_at'],
                'end_at'                => $row['created_at'],
                'bet_at'                => $row['created_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['transaction_id'],
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

        $this->utils->debug_log('PEGASUS ', $data);
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
        if(!empty($game_desc)){
            $row['game_description_id']     = isset($game_desc['id']) ? $game_desc['id'] : null;
            $row['game_type_id']            = isset($game_desc['game_type_id']) ? $game_desc['game_type_id'] : 'unknown';
            $row['game_name']               = isset($game_desc['game_name']) ? $game_desc['game_name'] : 'unknown';
        }

        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }


        if($this->enable_merging_rows){
            $row['bet_amount']      = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
            $row['payout_amount']   = isset($row['payout_amount']) ? $row['payout_amount'] : 0;
            $row['after_balance']   = $row['after_balance'] + $row['payout_amount'];
        }else{
            if($row['trans_type'] == 'PlaceBet'){
                $row['payout_amount'] = 0; 
            }else{
                $row['bet_amount'] = 0;
            }
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
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
                'win_amount'            => $details['payout_amount'],
                'settlement_datetime '  => $details['settlement_time'],
            ];
            if(isset($details['round_id']) && !$details['round_id']){
                $bet_details['round_id'] = $details['round_id']; 
            }
        }
        return $bet_details;
    }

    public function queryBetStatusByExternalUniqueId($external_uniqueid, $table_name){
        if(empty($external_uniqueid)){
            $external_uniqueid = '0';
        }
        $external_uniqueid = 'refund-'.$external_uniqueid;
        $where="t.external_uniqueid IN ('$external_uniqueid')";
        $params = [];

        return $this->queryBetStatusFromTrans($where, $params, $table_name);        
    }

    public function queryBetStatusFromTrans($where, $params = [], $table_name = null){ 
        if($where){
            $where = ' AND ' . $where;
        }          

$sql = <<<EOD
SELECT t.status as status,
t.round_id

FROM {$table_name} as t

WHERE t.trans_type = 'Refund'
{$where}
EOD;

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
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
t.transaction_id,
t.round_id,
t.external_uniqueid as external_uniqueid,
t.trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount,  
t.extra_info as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('PEGASUS SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

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
                $temp_game_record['round_no']           = $transaction['transaction_id'];
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
        $this->CI->utils->debug_log('PEGASUS SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

		$this->method   = $this->METHOD_MAP[$apiName];

        if(!empty($params)){
            $url = $url.'?'. http_build_query($params);
        }

		$this->CI->utils->debug_log('PEGASUS SEAMLESS (generateUrl) :', $this->method, $url);
		return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$this->CI->utils->debug_log('PEGASUS (processResultBoolean)', 'resultArr', $resultArr);	
        
        $success = false;

        if(isset($resultArr['code']) && $resultArr['code']==self::API_SUCCESS){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PEGASUS got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

    public function getPlayerBalanceById($player_id){
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());
        return $this->dBtoGameAmount($balance);
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like pegasus_seamless_wallet_transactions');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}
		return $tableName;
	}

    /* public function getLauncherLanguage($language){
        $language = strtolower($language);
        $lang='';
        switch ($language) {
            case 'en':
            case 'en-us':
            case 'en-US':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en';
                break;
            case 'th':
            case 'th-th':
            case 'th-TH':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
                $lang = 'th';
                break;
            case 'pt':
            case 'pt-br':
            case 'pt-BR':
            case 'pt-pt':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'pt';
                break;
            default: 
                $lang = $language;
                break;
        }
        return $lang;
    } */

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en-us',
            'zh_cn' => 'zh-cn',
            'id_id' => 'id-id',
            'vi_vn' => 'vi-vn',
            'ko_kr' => 'ko-kr',
            'th_th' => 'th-th',
            'hi_in' => 'hi-in',
            'pt_pt' => 'pt-pt',
            'es_es' => 'es-es',
            'kk_kz' => 'kk-kz',
            'pt_br' => 'pt-br',
            'ja_jp' => 'ja-jp',
        ]);
    }

    public function isSupportsLobby(){
        return $this->getSystemInfo('is_support_lobby', true);
    }

    public function getGameTypeLobbySupported(){
        return $this->getSystemInfo('game_type_lobby_supported', ["slots"]);
    }
}  
/*end of file*/