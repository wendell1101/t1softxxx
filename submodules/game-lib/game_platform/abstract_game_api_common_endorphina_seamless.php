<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * ENDORPHINA Seamless Integration
 * OGP-32673
 * ? use endorphina_seamless__service_api for service API
 * 
 * Game Platform ID: 6372
 *
 */

abstract class Abstract_game_api_common_endorphina_seamless extends Abstract_game_api {

    public  $URI_MAP, 
            $METHOD_MAP, 
            $url, 
            $method, 
            $currency, 
            $language, 
            $force_lang ,
            $original_transactions_table,
            $merchant_key,
            $disable_sign_validation, 
            $node_id,
            $home_url,
            $profile,
            $resetHistory,
            $enable_merging_rows,
            $demo_url,
            $edemo_account_id;

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
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','external_common_tokens'));

        $this->URI_MAP = array(
            self::API_queryForwardGame  => '',
            self::API_queryDemoGame     => '',
        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame => self::GET,                    
            self::API_queryDemoGame    => self::GET,                    
        ); 

        $this->url                              = $this->getSystemInfo('url',null);
        $this->currency                         = $this->getSystemInfo('currency',null);
        $this->language                         = $this->getSystemInfo('language',null);
        $this->force_lang                       = $this->getSystemInfo('force_lang', false);
        $this->merchant_key                     = $this->getSystemInfo('merchant_key', false);
        $this->disable_sign_validation          = $this->getSystemInfo('disable_sign_validation', false);
        $this->node_id                          = $this->getSystemInfo('node_id', null);
        $this->home_url                         = $this->getSystemInfo('home_url', null);
        $this->profile                          = $this->getSystemInfo('profile', null);
        $this->resetHistory                     = $this->getSystemInfo('resetHistory', null);
        $this->enable_merging_rows              = $this->getSystemInfo('enable_merging_rows', false);
        $this->demo_url                         = $this->getSystemInfo('demo_url', null);
        $this->edemo_account_id                 = $this->getSystemInfo('edemo_account_id', null);
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return ENDORPHINA_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }
    
    public function queryForwardGame($playerName, $extra = null)
    {   
        $this->CI->load->model(['external_common_tokens']);
        $this->utils->debug_log("ENDORPHINA SEAMLESS: (queryForwardGame)", $extra);
        $apiName        = self::API_queryForwardGame;
        $language       = $this->getLauncherLanguage($this->language, $extra['language']);
        $player         = $this->CI->player_model->getPlayerByUsername($playerName);
        $player_id      = $player->playerId;
        $existingToken  = $this->CI->external_common_tokens->getExternalToken($player_id,$this->getPlatformCode());
        $token          = $this->generateToken($player_id); #generates every launch game
        
        #extra_info, used in saving game_code in external_common_tokens since there's no any request params for game_code for launch URL and /session
        $extra_info = [
            'game_code' => $extra['game_code']
        ];

        #access demo game
        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo')){
            $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
            $demo_api_url = $this->queryDemoGame($game_code);
            $this->utils->debug_log("ENDORPHINA SEAMLESS: (queryForwardGame)-demo", $demo_api_url);
            if(isset($demo_api_url['url']) && $demo_api_url['url']){
                return [
                    'success'   => true,
                    'url'       => $demo_api_url['url']
                ];
            }
        }

        if($existingToken){
            #update token every launch game
            $this->CI->external_common_tokens->setPlayerToken($player_id, $token, $this->getPlatformCode());
            $this->CI->external_common_tokens->updatePlayerExternalExtraInfo($player_id, $token, $this->getPlatformCode(), json_encode($extra_info));
            $this->utils->debug_log("ENDORPHINA SEAMLESS: (queryForwardGame)-token existing,");
        }else{
            $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($player_id,$token,json_encode($extra_info),$this->getPlatformCode(),$this->currency);
            $this->utils->debug_log("ENDORPHINA SEAMLESS: (queryForwardGame)-token new token");
        }

        $params = [
            'exit' => $this->home_url,
            'nodeId' => $this->node_id,
            'token' => $token,
        ];

        if($language){
            $params['lang'] = $language;
        }
        if(isset($this->profile) && $this->profile){
            $params['profile'] = $this->profile;
        }
        if(isset($this->resetHistory) && $this->resetHistory){
            $params['resetHistory'] = $this->resetHistory;
        }
        $params['sign'] = $this->generateSign($params);
        $url = $this->generateUrl($apiName,$params);
        $this->utils->debug_log("ENDORPHINA SEAMLESS: (queryForwardGame)-URL", $url);
        return [
            'success' => true,
            'url'     => $url,
        ];
    }

    public function queryDemoGame($game_code){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForqueryDemoGame',
            'game_code' => $game_code
        );
        
        #https://edemo.endorphina.com/api/link/accountId/9653/returnUrl/player.staging.brlgateway.t1t.in
        $get_demo_links = $this->demo_url."/accountId/".$this->edemo_account_id;
        if(isset($this->home_url) && $this->home_url){
            $get_demo_links .= '/returnUrl/'.urlencode($this->home_url);
        }
        $this->url      = $get_demo_links;
        return $this->callApi(self::API_queryDemoGame,[],$context);
    }

    public function processResultForqueryDemoGame($params)
    {
        $game_code  = $this->getVariableFromContext($params, 'game_code');
        $resultArr  = $this->getResultJsonFromParams($params);
        $result     = [];
        $demo_links = $resultArr['ENDORPHINA'];
        $game_code  = 'endorphina'.$game_code;

        $demo_url = isset($demo_links[$game_code]) ? $demo_links[$game_code] : null;
        $result['url'] = $demo_url;
        $this->utils->debug_log("ENDORPHINA SEAMLESS: (processResultForqueryDemoGame)",$demo_url ,$demo_links);
        return array(true, $result);
    }

    public function generateToken($player_id){
        return md5($player_id.'-'.time());
    }

     #sign = SHA1HEX{param1param2paramNsalt}
     public function generateSign($params, $use_node_id = false){
        ksort($params);
		$concatenatedString = '';
        if($use_node_id){
			$concatenatedString = $this->node_id;
		}
        foreach ($params as $name => $value) {
            if ($name != 'sign') { 
                $concatenatedString .= $value;
            }
        }
        $concatenatedString .= $this->merchant_key;
        $sha1Hash = sha1($concatenatedString);
        return $sha1Hash;
    }


    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("ENDORPHINA SEAMLESS: (createPlayer)");

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for ENDORPHINA seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for ENDORPHINA seamless api";
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
        $this->CI->utils->debug_log("ENDORPHINA SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('ENDORPHINA-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time){
            $sqlTime='`original`.`datetime` >= ? AND `original`.`datetime` <= ?';
        }

        $trans_type = "original.trans_type in ('Bet')";
        if(!$this->enable_merging_rows){
            $trans_type = "original.trans_type in ('Bet','Win')";
        }
      
        $this->CI->utils->debug_log('ENDORPHINA SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.player,
    original.transaction_id,
    original.round_id,
    original.amount,
    original.bet_amount,
    original.win_amount,
    original.datetime,
    original.currency,
    original.trans_type,
    original.status,
    original.game as game_code,


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

		$this->CI->utils->debug_log('ENDORPHINA-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('ENDORPHINA SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }
        $result_amount = $row['win_amount'] - $row['bet_amount'];
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
                'player_username'       => $row['player']
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
                'start_at'              => $row['datetime'],
                'end_at'                => $row['datetime'],
                'bet_at'                => $row['datetime'],
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

        $this->utils->debug_log('ENDORPHINA ', $data);
        return $data;

    }

     /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        $this->CI->load->model('game_description_model');
        $game_code = str_replace(['endorphina', '@ENDORPHINA'], '', $row['game_code']);
        $game_desc = $this->CI->game_description_model->getGameDescByGameCode($game_code, $this->getPlatformCode());
        if(!empty($game_desc)){
            $row['game_description_id']     = isset($game_desc['id']) ? $game_desc['id'] : null;
            $row['game_type_id']            = isset($game_desc['game_type_id']) ? $game_desc['game_type_id'] : 'unknown';
            $row['game_name']   = isset($game_desc['game_name']) ? $game_desc['game_name'] : 'unknown';
        }

        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
        $tableName = $this->getTransactionsTable();
        $check_for_refund_status = $this->queryBetStatusByExternalUniqueId($row['external_uniqueid'],$tableName);
        if(!empty($check_for_refund_status) && isset($check_for_refund_status[0]['status'])){
            $row['status'] = $check_for_refund_status[0]['status'];
        }

        if($this->enable_merging_rows){
            $row['bet_amount'] = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
            $row['win_amount'] = isset($row['win_amount']) ? $row['win_amount'] : 0;
            $row['after_balance'] = $row['after_balance'] + $row['win_amount'];
        }else{
            if($row['trans_type'] == 'Bet'){
                $row['win_amount'] = 0; 
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
                'bet_id'            => $details['transaction_id'],
                'betting_datetime'  => $details['datetime'],
                'game_name'         => $details['game_code'],
                'bet_amount'        => $details['bet_amount'],
                'win_amount'        => $details['win_amount'],
            ];
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
t.datetime as transaction_date,
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


        $this->CI->utils->debug_log('ENDORPHINA SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

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
                $extra_info                             = @json_decode($transaction['extra_info'], true);
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
        $this->CI->utils->debug_log('ENDORPHINA SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

		$this->method   = $this->METHOD_MAP[$apiName];

        if(!empty($params)){
            $url = $url.'?'. http_build_query($params);
        }

		$this->CI->utils->debug_log('ENDORPHINA SEAMLESS (generateUrl) :', $this->method, $url);
		return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$this->CI->utils->debug_log('ENDORPHINA (processResultBoolean)', 'resultArr', $resultArr);	
        
        $success = false;

        if(isset($resultArr['status']['code']) && $resultArr['status']['code']==self::API_SUCCESS){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ENDORPHINA got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like endorphina_seamless_wallet_transactions');

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
    }
}  
/*end of file*/