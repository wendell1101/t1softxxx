<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * ASTAR Seamless Integration
 * OGP-31149
 * ? use astar_seamless__service_api for its service API
 *
 * Game Platform ID: 6351
 *
 */

abstract class Abstract_game_api_common_astar_seamless extends Abstract_game_api {

    public $URI_MAP , $METHOD_MAP, $url, $method, $currency, $language, $force_lang ,$portal_name,$key ,$original_transactions_table, $agent_id, $merchant_id, $bet_threshold, $authorization_token,$database_currency,$demo_url;

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

        $this->url                              = $this->getSystemInfo('url','https://astar111.com/');
        $this->currency                         = $this->getSystemInfo('currency','USD');
        $this->language                         = $this->getSystemInfo('language', 'en-us');
        $this->force_lang                       = $this->getSystemInfo('force_lang', false);
        $this->agent_id                         = $this->getSystemInfo('agent_id', null);
        $this->merchant_id                      = $this->getSystemInfo('merchant_id', null);
        $this->bet_threshold                    = $this->getSystemInfo('bet_threshold', $this->getDefaultBetThreshold());
        $this->authorization_token              = $this->getSystemInfo('authorization_token', null);
        $this->database_currency                = $this->getSystemInfo('database_currency', $this->currency);
        $this->demo_url                         = $this->getSystemInfo('demo_url', null);
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return ASTAR_SEAMLESS_GAME_API;
    }

    public function getCurrency(){
        return $this->currency;
    }
    
    public function getDefaultBetThreshold(){
        return [
            "maximum" => 0,
            "minimum" => 0
        ];
    }

    public function queryForwardGame($playerName, $extra = null)
    {   
        $this->utils->debug_log("ASTAR SEAMLESS: (queryForwardGame)", $extra);
        $apiName        = self::API_queryForwardGame;
        $player         = $this->CI->player_model->getPlayerByUsername($playerName);
        $player_id      = $player->playerId;
        $player_token   = $this->getPlayerToken($player_id);
        $language       = $this->getLauncherLanguage($this->language);
        if($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo'){
            $demo_url = $this->queryDemoGame();
            return [
                'success' => true,
                'url'     => $demo_url['url'],
            ];
        }

        $params = [
            'token'     => $this->database_currency.'-'.$player_token,
            'language'  => $language
        ];
        $url = $this->generateUrl($apiName,$params);
        return [
            'success' => true,
            'url'     => $url,
        ];
    }

    public function queryDemoGame(){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForqueryDemoGame'
        );
        $this->url = $this->demo_url;
        $this->method = self::GET;
        return $this->callApi(self::API_queryDemoGame,[],$context);
    }

    public function processResultForqueryDemoGame($params)
    {
        $resultArr = $this->getResultJsonFromParams($params);
        $result = [];
        if ($resultArr['state'] == 0) {
            $result['url'] = $resultArr['value'];
        }
        return array(true, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("ASTAR SEAMLESS: (createPlayer)");

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for ASTAR seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for ASTAR seamless api";
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
        $this->CI->utils->debug_log("ASTAR SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }


    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('ASTAR-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

      
        $this->CI->utils->debug_log('ASTAR SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.player_token,
    original.player,
    original.transaction_id,
    original.round as round_id,
    original.amount,
    original.settlement_amount,
    original.bet,
    original.win,
    original.rake,
    original.datetime,
    original.game_code,
    original.roomfee,
    original.valid_bet,
    original.round_number,
    original.table_type,
    original.table_name,
    original.table_id,
    original.currency,
    original.trans_type,
    original.trans_status as status,
    original.balance_adjustment_amount,
    original.balance_adjustment_method,
    original.before_balance,
    original.after_balance,
    original.external_uniqueid,
    original.game_platform_id,
    original.created_at,
    original.updated_at,
    original.response_result_id,

    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id

FROM {$table} as original
LEFT JOIN game_description as gd ON original.table_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
original.trans_type = 'Credit' AND 
{$sqlTime};
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('ASTAR-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('ASTAR SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }
        // $win = max(0, $row['win']);
        // $result_amount = $win - $row['bet'];
        // $settlement_amount = $row['settlement_amount'] > 0 ? $row['settlement_amount'] : $row['win'];
        $result_amount = $row['settlement_amount'] - $row['bet'];
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet'],
                'result_amount'         => $result_amount,
                'bet_for_cashback'      => $row['bet'],
                'real_betting_amount'   => $row['bet'],
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
            'bet_details' => [],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('ASTAR ', $data);
        return $data;

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
                $unknownGame->game_type_id, $row['game_name'], $row['game_name']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function getStandardDateTimeFormat($dateString){
        if($dateString){
            $dateTime = new DateTime($dateString);
            $formattedDate = $dateTime->format('Y-m-d H:i:s');
            return $formattedDate;
        }
    }

  
    public function queryTransactionByDateTime($startDate, $endDate){

$transTable = $this->getTransactionsTable();

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.updated_at as transaction_date,
t.balance_adjustment_amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.transaction_id as transaction_id,
t.round as round_id,
t.external_uniqueid as external_uniqueid,
t.trans_type as trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount,  
t.extra_info as extra_info
FROM {$transTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('ASTAR SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

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
        $this->CI->utils->debug_log('ASTAR SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

		$this->method   = $this->METHOD_MAP[$apiName];

		if($this->method == self::GET&&!empty($params)){
			$url = $url.$this->merchant_id . '?' . http_build_query($params);
        }

		$this->CI->utils->debug_log('ASTAR SEAMLESS (generateUrl) :', $this->method, $url);
		return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$this->CI->utils->debug_log('ASTAR (processResultBoolean)', 'resultArr', $resultArr);	
        
        $success = false;

        if(isset($resultArr['status']['code']) && $resultArr['status']['code']==self::API_SUCCESS){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ASTAR got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

    public function getPlayerBalanceById($player_id){
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());
        return $this->dBtoGameAmount($balance);
    }

    public function getTransactionsTable(){
        return $this->original_transactions_table; 
    }

    public function getLauncherLanguage($language){
        if($this->force_lang && $this->language){
            return $this->language;
        }
        $language = strtolower($language);
        $lang='';
        switch ($language) {
            case 'cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE:
                $lang = 'zh-cn';
                break;
            case 'id':
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $lang = 'id-id';
                break;
            case 'vn':
            case 'vi':
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $lang = 'vi-vn';
                break;
            case 'ko':
            case 'ko-kr':
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $lang = 'ko-kr';
                break;
            case 'th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case LANGUAGE_FUNCTION::PLAYER_LANG_THAI :
                $lang = 'th-th';
                break;
            case 'pt':
            case 'pt-br':
            case 'pt-pt':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE :
                $lang = 'pt-br';
                break;
            default: 
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function validateWhiteIP(){
        $success=false;
        $this->CI->load->model(['ip']);
        if(empty($this->backend_api_white_ip_list)){
            return true;
        }
        $ip=$this->utils->getIP();
        $this->utils->debug_log('ASTAR ip',$ip);
        if(is_array($this->backend_api_white_ip_list)){
            foreach ($this->backend_api_white_ip_list as $whiteIp) {
                if($this->utils->compareIP($ip, $whiteIp)){
                    $this->utils->debug_log('ASTAR found white ip', $whiteIp, $ip);
                    //found
                    return true;
                }
            }
        }
        $this->utils->debug_log('ASTAR validateWhiteIP status', $success);
        return $success;
    }
}  
/*end of file*/