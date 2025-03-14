<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';
/**
* Game Provider: Vivogaming Gaming 
* Wallet Type: Seamless
* Asian Brand: 
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @bermar.php.ph

    Related File
    -routes.php
    -vivogaming_service_api.php
**/

abstract class Abstract_game_api_common_vivogaming_seamless extends Abstract_game_api {
    use Year_month_table_module;

    const POST = 'POST';
    const GET = 'GET';   
    const DEFAULT_LOBBY_CODE = 'lobby';     
    const DEFAULT_TABLE_ID = '1';     
    
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];

    public $original_gamelogs_table;
    public $original_transactions_table;
    public $use_monthly_transactions_table;
    public $force_check_previous_transactions_table;
    public $force_language;

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model'));

        $this->url                  = $this->getSystemInfo('url');
        $this->game_launch_url      = $this->getSystemInfo('game_launch_url', 'http://games.vivogaming.com/');
        $this->demo_url             = $this->getSystemInfo('demo_url', '');
        $this->operator_id          = $this->getSystemInfo('operator_id');        
        $this->server_id            = $this->getSystemInfo('server_id');
        $this->language             = $this->getSystemInfo('language');        
        $this->currency             = $this->getSystemInfo('currency');        
        $this->hash_key             = $this->getSystemInfo('hash_key');
        $this->game_conversion_rate = $this->getSystemInfo('game_conversion_rate', 1);
        $this->home_url             = $this->getSystemInfo('home_url', $this->getHomeLink());        
        $this->cashier_url          = $this->getSystemInfo('cashier_url', $this->getHomeLink());                
        $this->logo_setup           = $this->getSystemInfo('logo_setup','VIVO_LOGO');
        $this->is_place_bet_cta     = $this->getSystemInfo('is_place_bet_cta','true');
        $this->default_selected_game_in_lobby = $this->getSystemInfo('default_selected_game_in_lobby','');
        $this->logo_url             = $this->getSystemInfo('logo_url','');
        $this->is_internal_pop      = $this->getSystemInfo('is_internal_pop','true');
        $this->default_game_type    = $this->getSystemInfo("default_game_type",'lobby');     
        
        $this->conversion_precision = $this->getSystemInfo('conversion_precision',4);     
        $this->conversion_rate      = $this->getSystemInfo('conversion_rate',1);     

        $this->show_response_message = $this->getSystemInfo('show_response_message', false);
        $this->show_hint = $this->getSystemInfo('show_hint', false);
        $this->force_language = $this->getSystemInfo('force_language', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        $this->URI_MAP = array(
            self::API_generateToken => '/service/login',        
            self::API_syncGameRecords => '/service/api/v1/profile/rounds'
        );
    
        $this->METHOD_MAP = array(
            self::API_generateToken => self::POST,        
            self::API_syncGameRecords => self::GET        
        );        

        $this->original_gamelogs_table = $this->getSystemInfo('original_gamelogs_table', 'vivogaming_seamless_game_logs');
        $this->original_transactions_table = $this->getSystemInfo('original_transactions_table', 'vivogaming_transactions');
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);

        if ($this->use_monthly_transactions_table) {
            $this->ymt_initialize($this->original_transactions_table, true);
            $this->original_transactions_table = $this->ymt_get_current_year_month_table();
        }
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }
    
	protected function getMethodName($apiName){		
		return (isset($this->METHOD_MAP[$apiName])?$this->METHOD_MAP[$apiName]:self::GET);
	}    

	public function generateUrl($apiName, $params) {	
        return $this->returnUnimplemented();
	}

	protected function getHttpHeaders($params){
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

		$headers = [];		
        
		return $headers;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("HABANERO SEAMLESS: (createPlayer)");

        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for habanero seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for habanero seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("VIVOGAMING SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("VIVOGAMING SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$this->CI->utils->debug_log('VIVOGAMING SEAMLESS (processResultBoolean)');	
        
        $success = false;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('VIVOGAMING SEAMLESS got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}
    
    public function buildUrl($url, $data){
        $params = $data;
        $params = http_build_query($params);
        $url = $url."?".$params;
        return $url;
    }

    /**
     * Game - 開啟遊戲 Launch Game
     * 
     * @param   string 
     * @param   array
     * @return  array
     * 
     */
    public function queryForwardGame($playerName, $extra){ 
        $this->utils->debug_log("VIVOGAMING SEAMLESS SEAMLESS: (queryForwardGame)");   
        
        # idenfity if demo game
        if($extra['game_mode'] == 'demo'){
           return [
              'success' => true,
              'url' => $this->demo_url,
           ]; 
        }

        if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($this->language);
        }

        if ($this->force_language) {
            $language = $this->language;
        }
			
        if (isset($extra['extra']['home_link'])) {
            $this->home_url = $extra['extra']['home_link'];
        }  
            
        $application = isset($extra['game_type']) ? $extra['game_type'] : null;
        if (empty($application) || $application == '_null') {
            $application = self::DEFAULT_LOBBY_CODE;
        } else {
            if (in_array($application, $this->getGameTypeLobbySupported())) {
                $application = self::DEFAULT_LOBBY_CODE;
            }

            if ($application == 'roulette' && !isset($extra['game_code'])) {
                $extra['game_code'] = self::DEFAULT_TABLE_ID;
            }
        }

        $token = $this->getToken($playerName);

        $data = array(            
            'token' => $token,            
            'operatorID' => $this->operator_id,
            'language' => $language,
            'application' => $application,
            'ServerID' => $this->server_id,
            'HomeURL' => $this->home_url,
        );

        if ($application != self::DEFAULT_LOBBY_CODE) {
            $data['logo_setup'] = $this->logo_setup;
            $data['isPlaceBetCTA'] = $this->is_place_bet_cta;
            $data['selectedGame'] = $this->default_selected_game_in_lobby;
            $data['Logourl'] = $this->logo_url;
            $data['IsInternalPop'] = $this->is_internal_pop;
        }

        if(!isset($extra['game_code'])){
            $data['tableID'] = $extra['game_code'];
        }
        
        $result = array(
            "success" => true,
            "url" => $this->buildUrl($this->game_launch_url, $data)
        );

        $this->utils->debug_log("VIVOGAMING_SEAMLESS_API", 'application', $application, 'extra', $extra, 'data', $data);
        return $result;
    }

    public function getToken($playerName){
        $token = $this->getPlayerTokenByUsername($playerName);        
        return $token;
    }

    public function getCurrency(){
        return $this->currency;
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
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'en'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th'; // thai
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt_br':
            case 'pt':
                $lang = 'pt'; // portuguese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function processResultForQueryTransaction($params) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName){
        $this->utils->debug_log("VIVOGAMING SEAMLESS: (queryPlayerBalance)");
        
        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        return $result;
    }

    public function syncOriginalGameLogs($token = false) {		
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token){
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        $table_name = $this->original_transactions_table;
        $roundIds = $this->queryGameInstanceIdsByDate($dateFrom, $dateTo, $table_name);        
        $gameRecords = $this->queryBetTransactionsByRoundIds($roundIds, $table_name);

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                $previous_table = $this->ymt_get_previous_year_month_table();     
                $previous_gameRecords = $this->queryBetTransactionsByRoundIds($roundIds, $previous_table);
                $gameRecords = array_merge($gameRecords, $previous_gameRecords);
            }
        }

        if($this->enable_merging_rows){
            $this->processGameRecordsFromTrans($gameRecords, $roundIds);
        }else{
            $this->processGameRecordsFromTransUnmerged($gameRecords, $roundIds);
        }
        return $gameRecords;
    }



    public function processGameRecordsFromTrans(&$gameRecords, $roundIds){
        $temp_game_records = [];


        foreach($gameRecords as $index => $record) {
            $playerId = $record['player_id'];
            $roundId = $record['round_id'];
            $uniqueId = $playerId.'-'.$roundId;

            if(!isset($temp_game_records[$uniqueId])){
                $temp_game_records[$uniqueId] = [];
                $temp_game_records[$uniqueId]['external_uniqueid'] = $uniqueId;
                $temp_game_records[$uniqueId]['player_username'] = $record['game_user_name'];

                
                $temp_game_records[$uniqueId]['max_index'] = $record['sync_index'];
                $temp_game_records[$uniqueId]['min_index'] = $record['sync_index'];
                $temp_game_records[$uniqueId]['before_balance'] = $record['before_balance'];
                $temp_game_records[$uniqueId]['after_balance'] = $record['after_balance'];

                $temp_game_records[$uniqueId]['start_at'] = $record['trans_time'];
                $temp_game_records[$uniqueId]['end_at'] = $record['trans_time'];

                $temp_game_records[$uniqueId]['player_id'] = $record['player_id'];
                $temp_game_records[$uniqueId]['round_id'] = $record['round_id'];
                $temp_game_records[$uniqueId]['game_id'] = $record['game_id'];
                $temp_game_records[$uniqueId]['game_code'] = $record['game_code'];
                $temp_game_records[$uniqueId]['game_description_name'] = $record['game_description_name'];
                $temp_game_records[$uniqueId]['trans_type'] = [];
                $temp_game_records[$uniqueId]['trans_desc'] = [];
                $temp_game_records[$uniqueId]['history'] = [];

                $temp_game_records[$uniqueId]['bet_amount'] = 0;
                $temp_game_records[$uniqueId]['win_amount'] = 0;
                $temp_game_records[$uniqueId]['canceled_amount'] = 0;

                $temp_game_records[$uniqueId]['game_code'] = $record['game_code'];
                $temp_game_records[$uniqueId]['game_name'] = $record['game_description_name'];
                $temp_game_records[$uniqueId]['game_description_id'] = $record['game_description_id'];
                $temp_game_records[$uniqueId]['game_type_id'] = $record['game_type_id'];
                $temp_game_records[$uniqueId]['game_user_name'] = $record['game_user_name'];

                $temp_game_records[$uniqueId]['status'] = Game_logs::STATUS_SETTLED;

                $temp_game_records[$uniqueId]['response_result_id'] = $record['response_result_id'];
                $temp_game_records[$uniqueId]['result_amount']=0;
                $temp_game_records[$uniqueId]['md5_sum'] = '';
            }

            $temp_game_records[$uniqueId]['game_platform_id'] = $record['game_platform_id'];            
            $temp_game_records[$uniqueId]['trans_type'][] = $record['trans_type'];
            $temp_game_records[$uniqueId]['trans_desc'][] = array($record['trans_type']=>$record['trans_desc']);
            $temp_game_records[$uniqueId]['history'][] = array($record['trans_type']=>$record['history']);

            if($record['sync_index'] > $temp_game_records[$uniqueId]['max_index']){                
                $temp_game_records[$uniqueId]['max_index'] = $record['sync_index'];
                $temp_game_records[$uniqueId]['after_balance'] = $record['after_balance'];
                $temp_game_records[$uniqueId]['end_at'] = $record['trans_time'];
            }

            if($record['sync_index'] < $temp_game_records[$uniqueId]['min_index']){                
                $temp_game_records[$uniqueId]['min_index'] = $record['sync_index'];                
                $temp_game_records[$uniqueId]['before_balance'] = $record['before_balance'];
                $temp_game_records[$uniqueId]['start_at'] = $record['trans_time'];
            }

            if($record['trans_type']=='BET'){
                $temp_game_records[$uniqueId]['bet_amount']+=$record['amount'];
            }

            if($record['trans_type']=='WIN'){
                $temp_game_records[$uniqueId]['win_amount']+=$record['amount'];
            }

            if($record['trans_type']=='CANCELED_BET'){
                $temp_game_records[$uniqueId]['canceled_amount']+=$record['amount'];
                $temp_game_records[$uniqueId]['status'] = Game_logs::STATUS_CANCELLED;
            }
            
            if($temp_game_records[$uniqueId]['win_amount']>0){
                $temp_game_records[$uniqueId]['result_amount']=$temp_game_records[$uniqueId]['win_amount']-$temp_game_records[$uniqueId]['bet_amount'];
            }
        }
        
        $gameRecords = array_values($temp_game_records);
        unset($temp_game_records);
    }

    public function processGameRecordsFromTransUnmerged(&$gameRecords, $roundIds){
        $temp_game_records = [];
        foreach($gameRecords as $index => $record) {
            $temp_game_records[$index]['amount']                = isset($record['amount']) ? $record['amount'] : 0; 
            $temp_game_records[$index]['bet_amount']            = isset($record['amount']) ? $record['amount'] : 0; 
            $temp_game_records[$index]['result_amount']         = isset($record['amount']) ? $record['amount'] : 0; 
            $temp_game_records[$index]['game_id']               = isset($record['game_id']) ? $record['game_id'] : null;
            $temp_game_records[$index]['status']                = Game_logs::STATUS_SETTLED;
            $temp_game_records[$index]['player_id']             = isset($record['player_id']) ? $record['player_id'] : null;
            $temp_game_records[$index]['player_username']       = isset($record['game_user_name']) ? $record['game_user_name'] : null;
            $temp_game_records[$index]['after_balance']         = isset($record['after_balance']) ? $record['after_balance'] : null;
            $temp_game_records[$index]['before_balance']        = isset($record['before_balance']) ? $record['before_balance'] : null; 
            $temp_game_records[$index]['start_at']              = isset($record['trans_time']) ? $record['trans_time'] : null; 
            $temp_game_records[$index]['end_at']                = isset($record['trans_time']) ? $record['trans_time'] : null; 
            $temp_game_records[$index]['external_uniqueid']     = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null; 
            $temp_game_records[$index]['round_id']              = isset($record['round_id']) ? $record['round_id'] : null; 
            $temp_game_records[$index]['min_index']             = isset($record['sync_index']) ? $record['sync_index'] : null; 
            $temp_game_records[$index]['trans_type']            = isset($record['trans_type']) ? $record['trans_type'] : null; 
            $temp_game_records[$index]['game_code']             = isset($record['game_code']) ? $record['game_code'] : null;
            $temp_game_records[$index]['game_name']             = isset($record['game_description_name']) ? $record['game_description_name'] : null;
            $temp_game_records[$index]['response_result_id']    = isset($record['response_result_id']) ? $record['response_result_id'] : null;
        }
        $gameRecords = array_values($temp_game_records);
        unset($temp_game_records);
    }

    public function queryBetTransactionsByRoundIds($roundIds, $table_name){
        if(empty($roundIds)){
            $roundIds = ['0'];
        }
        $roundIds = implode("','", $roundIds);
        $where="t.round_id IN ('$roundIds')";
        $params = [];

        return $this->queryBetTransactionsFromTrans($where, $params, $table_name);        
    }

    public function queryBetTransactionsFromTrans($where, $params = [], $table_name = null){ 
        if($where){
            $where = ' AND ' . $where;
        }          
        
        if (empty($table_name)) {
            $table_name = $this->original_transactions_table;
        }
        
        $this->utils->debug_log(__CLASS__, __FUNCTION__, $this->getPlatformCode(), 'table_name', $table_name);

$sql = <<<EOD
SELECT t.id as sync_index,
t.game_id as game_id,
t.trans_id as trans_id,
t.trans_type as trans_type,
t.amount as amount,
t.before_balance as before_balance,
t.after_balance as after_balance,
t.trans_desc as trans_desc,
t.round_id as round_id,
t.history as history,
t.is_round_finished as is_round_finished,
t.hash as hash,
t.session_id as session_id,
t.trans_time as trans_time,
t.currency as currency,

t.created_at as created_at,
t.updated_at as updated_at,
t.game_platform_id as game_platform_id,
t.response_result_id as response_result_id,
t.external_uniqueid as external_uniqueid,


game_provider_auth.player_id,
game_provider_auth.login_name game_user_name,
gd.id as game_description_id,
gd.english_name as game_description_name,
gd.game_code as game_code,
gd.game_type_id

FROM {$table_name} as t

LEFT JOIN game_description as gd ON gd.external_game_id = t.game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gt.id = gd.game_type_id
JOIN game_provider_auth ON game_provider_auth.player_id = t.player_id AND game_provider_auth.game_provider_id=?

WHERE 1
{$where}
ORDER BY t.id, t.trans_time;

EOD;

$params=[$this->getPlatformCode(), $this->getPlatformCode()];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function queryGameInstanceIdsByDate($dateFrom, $dateTo, $table_name){
        $sqlTime='t.updated_at >= ? and t.updated_at <= ?';
        $sql = <<<EOD
SELECT DISTINCT t.round_id as round_id
FROM {$table_name} as t
WHERE
{$sqlTime};
EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $data = array_column($result,'round_id');
        return $data;
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());            
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }
    
    private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_id'];
        $extra = array('game_code' => $external_game_id,'game_name' => !empty($row['game_name']) ? $row['game_name'] : $external_game_id);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->utils->debug_log("VIVOGAMING SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow)");
        
        if($this->enable_merging_rows){
            $result_amount = $this->convertAmountToDB($row['win_amount'] - $row['bet_amount']);
            $bet_amount = $this->convertAmountToDB($row['bet_amount']);
        }else{
            if($row['trans_type'] == 'BET'){
                $win_amount = 0;
                $bet_amount = isset($row['amount']) ? $this->convertAmountToDB($row['amount']) : 0;
            }else{
                $win_amount = isset($row['amount']) ? $this->convertAmountToDB($row['amount']) : 0;
                $bet_amount = 0;
            }
            $result_amount = $win_amount - $bet_amount;
        }

        if(empty($row['md5_sum'])){            
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return  [           
            'game_info'=>[
                'game_type_id' => $row['game_type_id'], 
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'], 
                'game_type' => null, 
                'game' => $row['game_name']
            ],
            'player_info'=>[
                'player_id' => $row['player_id'], 
                'player_username' => $row['player_username']
            ],
            'amount_info'=>[
                'bet_amount' => $bet_amount, 
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount, 
                'real_betting_amount' => $bet_amount,
                'win_amount' => null, 
                'loss_amount' => null, 
                'after_balance' => $row['after_balance']/$this->game_conversion_rate,
                'before_balance' => $row['before_balance']/$this->game_conversion_rate
            ],
            'date_info'=>[
                'start_at' => $this->gameTimeToServerTime($row['start_at']), 
                'end_at' => $this->gameTimeToServerTime($row['end_at']), 
                'bet_at' => $this->gameTimeToServerTime($row['start_at']),
                'updated_at' => $this->gameTimeToServerTime($row['end_at'])
            ],
            'flag'=>Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0, 
                'external_uniqueid' => $row['external_uniqueid'], 
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'], 
                'response_result_id' => $row['response_result_id'], 
                'sync_index' => $row['min_index'],
                'bet_type' => null 
            ],
            'bet_details' => $this->preprocessBetDetails($row),
            'extra' => [],            
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function rebuildBetDetailsFormat($row, $game_type) {
        $bet_details = [];

        /* switch ($game_type) {
            case self::GAME_TYPE_LIVE_DEALER:
                if (isset($row['history'])) {
                    $bet_details['history'] = json_encode($row['history']);
                }
                break;
            default:
                $bet_details = $this->defaultBetDetailsFormat($row);
                break;
        } */

        if (empty($bet_details) && !empty($row['bet_details'])) {
            $bet_details = is_array($row['bet_details']) ? $row['bet_details'] : json_decode($row['bet_details'], true);
        }

        if (empty($bet_details)) {
            $bet_details = $this->defaultBetDetailsFormat($row);
        }

        return $bet_details;
    }

    public function getSeamlessTransactionTable(){
        return $this->original_transactions_table;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getSeamlessTransactionTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.trans_time transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.trans_id as transaction_id,
t.external_uniqueid as external_uniqueid,
t.trans_type,
t.raw_data as extra_info
FROM {$original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc, t.id asc;
EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        #instead query unsettled,query settled to check if have settlement
        $sqlTime='vivo.updated_at >= ? AND vivo.updated_at <= ?';
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
vivo.round_id as round_id, 
vivo.trans_id as transaction_id, 
vivo.created_at as transaction_date,
vivo.round_id as external_uniqueid,
vivo.player_id,
if(vivo.trans_type = 'BET', vivo.amount, 0) as deducted_amount,
if(vivo.trans_type != 'BET', vivo.amount, 0) as added_amount,
gd.id as game_description_id,
gd.game_type_id,
{$this->getPlatformCode()} as game_platform_id,
count(*) as row_count

from {$original_transactions_table} as vivo
LEFT JOIN game_description as gd ON vivo.game_id = gd.external_game_id and gd.game_platform_id=?
where
{$sqlTime}
GROUP BY vivo.round_id
having row_count = 1
EOD;


        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('==> vivo getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $original_transactions_table = $this->getSeamlessTransactionTable();
            $roundId = $row['round_id'];
            $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $roundId,  'trans_type' => 'WIN']);
            $cancelExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $roundId,  'trans_type' => 'CANCELED_BET']);
            if(!$payoutExist && !$cancelExist){
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                unset($row['row_count']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('VIVO SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $external_uniqueid,  'trans_type' => 'WIN']);
        $cancelExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $external_uniqueid,  'trans_type' => 'CANCELED_BET']);
        if($payoutExist || $cancelExist){
            return array('success'=>true, 'status'=> Game_logs::STATUS_SETTLED);
        }
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }

}//end of class
