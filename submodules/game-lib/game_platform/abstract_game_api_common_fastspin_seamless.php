<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * FASTSPIN Seamless Integration
 * OGP-32970
 * ? use fastspin_seamless_service_api for service API
 * 
 * Game Platform ID: 6381
 *
 */

abstract class Abstract_game_api_common_fastspin_seamless extends Abstract_game_api {

    public  $URI_MAP, 
            $METHOD_MAP, 
            $url, 
            $method, 
            $currency, 
            $language, 
            $force_lang ,
            $merchant_code,
            $security_key,
            $enable_merging_rows,
            $game_lobby
           ;

    const POST                  = 'POST';
    const GET                   = 'GET';
    const API_SUCCESS           =  0;

    const MD5_FIELDS_FOR_MERGE = [
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'bet_amount',
        'win_amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'ip','game_logs','game_description_model','external_common_tokens'));

        $this->URI_MAP = array(
            self::API_queryForwardGame  => '/getAuthorize',
            self::API_queryDemoGame     => '/getAuthorize',
        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame => self::POST,                    
            self::API_queryDemoGame    => self::POST,                    
        ); 

        $this->url                    = $this->getSystemInfo('url',null);
        $this->currency               = $this->getSystemInfo('currency',null);
        $this->language               = $this->getSystemInfo('language',null);
        $this->force_lang             = $this->getSystemInfo('force_lang', false);
        $this->merchant_code          = $this->getSystemInfo('merchant_code', null);
        $this->security_key           = $this->getSystemInfo('security_key', null);
        $this->enable_merging_rows    = $this->getSystemInfo('enable_merging_rows', false);
        $this->game_lobby             = $this->getSystemInfo('game_lobby', 'FS');
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return FASTSPIN_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }
    
    public function queryForwardGame($playerName, $extra = null)
    {   
        $this->CI->load->model(['external_common_tokens']);
        $this->utils->debug_log("FASTSPIN SEAMLESS: (queryForwardGame)", $extra);
        $language       = $this->getLauncherLanguage($this->language, $extra['language']);
        $player         = $this->CI->player_model->getPlayerByUsername($playerName);
        $gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
        $player_id      = $player->playerId;
        $player_token   = $this->getPlayerToken($player_id);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $apiName = self::API_queryForwardGame;

        $params = [
            'merchantCode'  => $this->merchant_code,
            "acctInfo" => [
                'acctId' => $gameUsername,
                'currency' => $this->currency
            ],
            'token'         => $player_token,
            'acctIp'        => $this->utils->getIp(),
            'language'      => $language
        ];

        if(isset($extra['game_code']) && !empty($extra['game_code'])){
            $params['game'] = $extra['game_code'];
        }else{
            $params['lobby'] = $this->game_lobby;
        }


        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo')){
            $params['fun'] = true;
            $apiName = self::API_queryDemoGame;
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

        $this->utils->debug_log("FASTSPIN SEAMLESS: (processResultForQueryForwardGame)", $resultArr);
        if($success){
            $result['url'] = $resultArr['gameUrl'];
        }
        return array(true, $result);
    }

    protected function customHttpCall($ch, $params)
    {

        if ($this->method == self::POST) {
            $digest = $this->generateKey($params);
            $header = [
                'Content-Type: application/json',
                'API: getAuthorize',
                'DataType: JSON',
                'Digest: '.$digest,
            ];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
    }

    public function generateKey($params){
        $data = json_encode($params);
        return md5($data.$this->security_key);
    }
    

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("FASTSPIN SEAMLESS: (createPlayer)");

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for FASTSPIN seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for FASTSPIN seamless api";
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
        $this->CI->utils->debug_log("FASTSPIN SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('FASTSPIN-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time){
            $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        }

        $trans_type = "original.trans_type in ('placebet')";
        if(!$this->enable_merging_rows){
            $trans_type = "original.trans_type in ('placebet','payout')";
        }
      
        $this->CI->utils->debug_log('FASTSPIN SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.win_amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.transfer_id as transaction_id,
    original.reference_id,
    original.amount,
    original.bet_amount,
    original.win_amount,
    original.game_code,
    original.currency,
    original.trans_type,
    original.status,
    original.extra_info,
    original.acct_id as player_username,

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

		$this->CI->utils->debug_log('FASTSPIN-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('FASTSPIN SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
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

        $this->utils->debug_log('FASTSPIN ', $data);
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
            $tableName              = $this->getTransactionsTable();
            $getTotalWinAmount      = $this->queryGetTotalAmount($row['transaction_id'],$tableName);
            $win_amount             = $getTotalWinAmount[0]['total_win_amount'];
            $row['win_amount']      = $win_amount;
            $row['bet_amount']      = isset($row['bet_amount']) ? $row['bet_amount'] : 0;
            $row['win_amount']      = isset($row['win_amount']) ? $row['win_amount'] : 0;
            $row['after_balance']   = $row['after_balance'] + $row['win_amount'];
        }else{
            if($row['trans_type'] == 'placebet'){
                $row['win_amount'] = 0; 
            }else{
                $row['bet_amount'] = 0;
            }
        }
    }

    public function queryGetTotalAmount($transactionId, $table_name = null){    
$sql = <<<EOD
SELECT sum(t.amount) as total_win_amount

FROM {$table_name} as t

WHERE t.reference_id = $transactionId
EOD;
        

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, []);
        return $result;
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
                'win_amount'            => $details['win_amount'],
                'settlement_datetime '  => $details['updated_at'],
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
t.transfer_id as transaction_id,
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


        $this->CI->utils->debug_log('FASTSPIN SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

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
        $this->CI->utils->debug_log('FASTSPIN SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

		$this->method   = $this->METHOD_MAP[$apiName];

        // if(!empty($params)){
        //     $url = $url.'?'. http_build_query($params);
        // }

		$this->CI->utils->debug_log('FASTSPIN SEAMLESS (generateUrl) :', $this->method, $url);
		return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$this->CI->utils->debug_log('FASTSPIN (processResultBoolean)', 'resultArr', $resultArr);	
        
        $success = false;

        if(isset($resultArr['code']) && $resultArr['code']==self::API_SUCCESS){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('FASTSPIN got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like fastspin_seamless_wallet_transactions');

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
                $lang = 'en_US';
                break;
            case 'th':
            case 'th-th':
            case 'th-TH':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
                $lang = 'th_TH';
                break;
            case 'pt':
            case 'pt-br':
            case 'pt-BR':
            case 'pt-pt':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'pt_PT';
                break;
            default: 
                $lang = $language;
                break;
        }
        return $lang;
    }
}  
/*end of file*/