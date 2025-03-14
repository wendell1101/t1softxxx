<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * REDGENN Seamless Integration
 * ? use redgenn_seamless_service_api for service API
 * 
 */

class Game_api_redgenn_seamless_api extends Abstract_game_api {

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
            $game_platform_id,
            $partner,
			$dont_response_on_round_bet_on_player,
            $conversion_rate,
            $offer_url,
            $login,
            $password,
            $wlcode
           ;

    const POST                  = 'POST';
    const GET                   = 'GET';
    const API_CreateOffer       = 'CreateOffer';
    const API_CreateAddOffer    = 'AddOffer';
    

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
            self::API_queryForwardGame  => '',
            self::API_queryDemoGame     => '',
            self::API_CreateOffer       => '',
            self::API_CreateAddOffer       => '',

        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame  => self::GET,                    
            self::API_queryDemoGame     => self::GET,                    
            self::API_CreateOffer       => self::GET,                    
            self::API_CreateAddOffer    => self::GET,                    
        ); 

        $this->url                    = $this->getSystemInfo('url',null);
        $this->currency               = $this->getSystemInfo('currency',null);
        $this->language               = $this->getSystemInfo('language',null);
        $this->force_lang             = $this->getSystemInfo('force_lang', false);
        $this->enable_merging_rows    = $this->getSystemInfo('enable_merging_rows', false);
        $this->partner                = $this->getSystemInfo('partner', false);
        $this->dont_response_on_round_bet_on_player = $this->getSystemInfo('dont_response_on_round_bet_on_player', false);
        $this->conversion_rate 	      = $this->getSystemInfo('conversion_rate',100); #balance should be converted to nondecimal amount 
        $this->offer_url              = $this->getSystemInfo('offer_url', null);
        $this->login                  = $this->getSystemInfo('login', null);
        $this->password               = $this->getSystemInfo('password', null);
        $this->wlcode                 = $this->getSystemInfo('wlcode', null);
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
        if (!$this->validateWhitePlayer($playerName)) {
            $gamePlatformId = $this->getPlatformCode();
            $this->CI->utils->debug_log("REDGENN_PLAYSON ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

        $this->CI->load->model(['external_common_tokens']);
        $this->utils->debug_log("REDGENN SEAMLESS: (queryForwardGame)", $extra);
        $language       = isset($this->language) ? $this->language : $extra['language'];
        $player         = $this->CI->player_model->getPlayerByUsername($playerName);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];

        if(isset($extra['game_mode']) && ($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'demo')){
            #demo
            $apiName = self::API_queryDemoGame;
            $params = [
                'gameName' => $extra['game_code'],
                'key' => 'demokey',
                'partner' => $this->partner,
                'lang' =>  $this->getLauncherLanguage($language),
                'platform' => $is_mobile ? 'mob' : 'desktop',
                'demo' => true,
            ];
        }else{
            #real
            $player_id      = $player->playerId;
            $token          = $this->generateToken($player_id);
            $apiName        = self::API_queryForwardGame;
            // $this->CI->external_common_tokens->setPlayerToken($player_id, $token, $this->getPlatformCode());
            $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($player_id,$token,null,$this->getPlatformCode(),$this->currency);
            $params = [
                'gameName'  => $extra['game_code'],
                'key'       => $token,
                'partner'   => $this->partner,
                'lang'      => $this->getLauncherLanguage($language),
                'platform' => $is_mobile ? 'mob' : 'desktop',
            ];
        }

        return ['success' => true, 'url' => $this->generateUrl($apiName, $params)];
    }

    public function createFreeRound($playerName, $extra = []) {
        return $this->CreateOffer($playerName, $extra);
    }

    public function checkRequiredParameters($extra, $required_parameters) {
        foreach ($required_parameters as $param) {
            if (!property_exists($extra, $param) || empty($extra->$param)) {
                return false;
            }
        }
        
        return true;
    }

    public function CreateOffer($playerName, $extra = []) {
        #request custom validation
        $game_username  = $this->getGameUsernameByPlayerUsername($playerName);
        $player_id  = $this->getPlayerIdByGameUsername($game_username);
        $validation_boolean = true;
        $message = '';
        $required_parameters = ['player_username', 'lifetime', 'game', 'spins', 'totalbet'];
        $is_parameters_valid = $this->checkRequiredParameters($extra, $required_parameters);
        
        if(!$game_username){
            $validation_boolean = false;
            $message = 'Player not found!';
        }

        if(!$is_parameters_valid){
            $validation_boolean = false;
            $message = 'Invalid parameters';
        }

        if(!$validation_boolean){
            return ['success' => false, 'message' => $message];
        }

        $offer = $this->getSecureId('free_round_bonuses', 'transaction_id', true, 'R', 29);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateOffer',
            'lifetime' => $extra->lifetime,
            'offer' => $offer,
            'currency' => $this->currency,
            'game_username' => $game_username,
            'game' => $extra->game,
            'player_id' => $player_id,
            'spins' => $extra->spins,
            'extra' => $extra
        );

        $params = array(
            'login' => $this->login,
            'password' => $this->password,
            'wlcode' => $this->wlcode,
            'cm' => 'create_offer',
            'offer' => $offer,
            'game' => $extra->game,
            'currency' => $this->currency,
            'spins' => $extra->spins,
            'totalbet' => $extra->totalbet,
        );

        return $this->callApi(self::API_CreateOffer, $params, $context);
    }

    public function processResultForCreateOffer($params) {
        $offer = $this->getVariableFromContext($params, 'offer');
        $lifetime = $this->getVariableFromContext($params, 'lifetime');
        $extra = $this->getVariableFromContext($params, 'extra');
        $spins = $this->getVariableFromContext($params, 'spins');
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $game_username = $this->getVariableFromContext($params, 'game_username');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->_xmlToArray($this->getResultTextFromParams($params));
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        if ($success){
            $offer_data = [
                'game_username' => $game_username,
                'offer' => $offer
            ];
            $is_success_adding_player = $this->add_offer($offer_data);
            if($is_success_adding_player){
                $return = [
                    'offer' => $offer,
                    'lifetime' => $lifetime,
                ];
                $this->CI->load->model(array('free_round_bonus_model'));
    
                $data = [
                    'player_id' => $player_id,
                    'game_platform_id' => $this->getPlatformCode(),
                    'free_rounds' => $spins,
                    'transaction_id' => $offer, 
                    'currency' => $this->currency,
                    'expired_at' => $lifetime,
                    'extra' => $extra #request params made on dev function
                ];
    
                $this->CI->free_round_bonus_model->insertTransaction($data);
            }
        }
        else {
            $return = [
                'message' => 'error'
            ];
        }
        return array($success, $return);
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
        
		$success = false;
		if(isset($resultArr['@attributes']['status']) && $resultArr['@attributes']['status'] == 'ok'){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('REDGENN got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}


    public function add_offer($data){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAdd_offer'
        );

        $params = array(
            'login' => $this->login,
            'password' => $this->password,
            'wlcode' => $this->wlcode,
            'cm' => 'add_offer',
            'wlid' => $data['game_username'],
            'offer' => $data['offer'],
        );

        return $this->callApi(self::API_CreateAddOffer, $params, $context);
    }


    public function processResultForAdd_offer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->_xmlToArray($this->getResultTextFromParams($params));
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        if ($success){
            return true;
        }
        return false;
    }

    public function _xmlToArray($xml_string)
    {
        $xml = simplexml_load_string($xml_string);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        return $array;
    }

    public function generateToken($player_id){
        return md5($player_id.'-'.time());
    }
    
    protected function customHttpCall($ch, $params)
    {

        if ($this->method == self::POST) {
            $header = [
                'Content-Type: application/json',
            ];
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));  
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("REDGENN SEAMLESS: (createPlayer)");

        if (!$this->validateWhitePlayer($playerName)) {
            $gamePlatformId = $this->getPlatformCode();
            $this->CI->utils->debug_log("REDGENN_PLAYSON ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

        # create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for REDGENN seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for REDGENN seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){
        if (!$this->validateWhitePlayer($userName)) {
            $gamePlatformId = $this->getPlatformCode();
            $this->CI->utils->debug_log("REDGENN_PLAYSON ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){
        if (!$this->validateWhitePlayer($userName)) {
            $gamePlatformId = $this->getPlatformCode();
            $this->CI->utils->debug_log("REDGENN_PLAYSON ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

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
        $this->CI->utils->debug_log("REDGENN SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        return $currentTableData;
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $this->utils->debug_log('REDGENN-syncOrig', $table, $dateFrom, $dateTo);           
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time){
            $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        }

        $groupBy = 'group by original.round_id';
        $trans_type = "original.trans_type in ('roundbet')";
        if(!$this->enable_merging_rows){
            $trans_type = "original.trans_type in ('roundbet','roundwin')";
            $groupBy = '';
        }
      
        $this->CI->utils->debug_log('REDGENN SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.win_amount', 'original.after_balance', 'original.updated_at'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.player_id,
    original.transaction_id as transaction_id,
    original.round_id as round_id,
    original.bet,
    original.win,
    original.bet_amount,
    original.win_amount,
    original.game_id as game_code,
    original.currency,
    original.trans_type,
    original.status,
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
{$trans_type} AND 
{$sqlTime}
{$groupBy};
EOD;
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('REDGENN-syncSQL', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->CI->utils->debug_log('REDGENN SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
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
                'end_at'                => $row['updated_at'],
                'bet_at'                => $row['created_at'],
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

        $this->utils->debug_log('REDGENN ', $data);
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
            $row['after_balance']   = $row['after_balance'] + $row['win_amount'];

            #get total win amounts including free spins
            $table = $this->getTransactionsTable();
            $total_win = $this->queryTotalBetAmountByRound($table, $row['round_id']);
            $row['win_amount'] = $total_win;
        }else{
            if($row['trans_type'] == 'roundbet'){
                $row['win_amount'] = 0; 
            }else{
                $row['bet_amount'] = 0;
            }
        }

    }

    private function queryTotalBetAmountByRound($table_name, $round_id){
        $this->CI->load->model('original_game_logs_model');

        $sqlRound="original.round_id = ? AND original.game_platform_id = ? AND original.trans_type = ?";

        $sql = <<<EOD
SELECT
sum(original.win_amount) as total_win
FROM {$table_name} as original
WHERE
{$sqlRound}
EOD;
        $params=[
            $round_id,
            $this->game_platform_id,
            'roundwin'
        ];

        $this->CI->utils->debug_log('queryTotalBetAmountByRound sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        if(isset($result['total_win'])){
            return $result['total_win'];
        }
        return null;
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
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];


        $this->CI->utils->debug_log('REDGENN SEAMLESS GAME (queryTransactionByDateTime)', 'sql', $sql, 'params',$params);

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
        $this->CI->utils->debug_log('REDGENN SEAMLESS (generateUrl)', $apiName, $params);		
		$apiUri         = $this->URI_MAP[$apiName];
		$url            = $this->url . $apiUri;		

        if($apiName == self::API_CreateOffer || $apiName == self::API_CreateAddOffer){
            $url = $this->offer_url;
        }

		$this->method   = $this->METHOD_MAP[$apiName];
        if(!empty($params)){
            $url = $url.'?'. http_build_query($params);
        }
		$this->CI->utils->debug_log('REDGENN SEAMLESS (generateUrl) :', $this->method, $url);

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
                $lang = 'ph';
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
            case 'ko':
            case 'ko-ko':
            case 'ko-kr':
            case 'ko-KR':
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE:
                $lang = 'ko';
                break;
            default: 
                $lang = 'en';
                break;
        }
        return $lang;
    }
}  
/*end of file*/