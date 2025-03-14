<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Yggdrasil 
* @integrator @bermar.php.ph

    Related File
    -routes.php
    -ygg_service_api.php
**/

abstract class Abstract_game_api_common_ygg_seamless extends Abstract_game_api {
    const ORIGINAL_GAME_LOGS = '';
    const ORIGINAL_TRANSACTIONS = 'ygg_seamless_wallet_transactions';

	const TRANSTYPE_PLAYERINFO = 'playerinfo';
	const TRANSTYPE_GETBALANCE = 'getbalance';
	const TRANSTYPE_WAGER = 'wager';
    const TRANSTYPE_CANCEL_WAGER = 'cancelwager';
    const TRANSTYPE_APPEND_WAGER_RESULT = 'appendwagerresult';
    const TRANSTYPE_END_WAGER= 'endwager';
    const TRANSTYPE_CAMPAIGN_PAYOUT= 'campaignpayout';

    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','game_code','game_name'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance'];

    const URI_MAP = array(
        self::API_queryForwardGame => '',        
        self::API_syncGameRecords => '/restless/getusersbetdata',
    );

    const ODDS_MAP = [
        'red' => 2,
        'black' => 2,
        'white' => 14
    ];

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    ############### SEAMLESS SERVICE API CODES ###################  
    const SUCCESS = '0x00';    
    const ERROR_SERVICE_NOT_AVAILABLE = '0x01';
	const ERROR_INVALID_SIGN = '0x02';
	const ERROR_INVALID_PARAMETERS = '0x03';
	const ERROR_INSUFFICIENT_BALANCE = '0x04';
	const ERROR_SERVER = '0x05';
	const ERROR_CANNOT_FIND_PLAYER = '0x06';
	const ERROR_TRANSACTION_ALREADY_EXIST = '0x07';
	const ERROR_IP_NOT_ALLOWED = '0x08';
	const ERROR_BET_DONT_EXIST = '0x09';	
	const ERROR_GAME_UNDER_MAINTENANCE = '0x10';
	const ERROR_CONNECTION_TIMED_OUT = '0x11';
	const ERROR_REFUND_PAYOUT_EXIST = '0x12';	

	
	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS=>200,
		self::ERROR_SERVICE_NOT_AVAILABLE=>404,		
		self::ERROR_INVALID_SIGN=>400,
		self::ERROR_INVALID_PARAMETERS=>400,
		self::ERROR_INSUFFICIENT_BALANCE=>406,
		self::ERROR_SERVER=>500,
		self::ERROR_CANNOT_FIND_PLAYER=>400,	
		self::ERROR_TRANSACTION_ALREADY_EXIST=>409,	
		self::ERROR_IP_NOT_ALLOWED=>401,
		self::ERROR_BET_DONT_EXIST=>400,
		self::ERROR_REFUND_PAYOUT_EXIST=>400,
		self::ERROR_GAME_UNDER_MAINTENANCE=>503,
		self::ERROR_CONNECTION_TIMED_OUT=>423,
	];
    ##############################################################

    public function __construct(){
        parent::__construct();        
        $this->CI->load->model(['common_token']);

        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTIONS;
        $this->api_url = $this->getSystemInfo('url');
        $this->currency = $this->getSystemInfo('currency');
        $this->country = $this->getSystemInfo('country');
        $this->organization = $this->getSystemInfo('organization');

        $this->game_launch_url = $this->getSystemInfo('game_launch_url', 'https://seamless-stage.248ka.com/restless/launchClient.html');
        $this->game_launch_url_demo = $this->getSystemInfo('game_launch_url_demo', 'https://static-fra.pff-ygg.com/init/launchClient.html');
        $this->game_launch_url_demo_org = $this->getSystemInfo('game_launch_url_demo_org', 'demo');
        $this->enabled_game_logs_unsettle      = $this->getSystemInfo('enabled_game_logs_unsettle', true);        

        $this->request = null;
        $this->use_referrer = $this->getSystemInfo('use_referrer', true);

        //FOR TESTING
        $this->trigger_player_info_error_response = $this->getSystemInfo('trigger_player_info_error_response', 0);
        $this->trigger_wager_error_response = $this->getSystemInfo('trigger_wager_error_response', 0);
        $this->trigger_cancelwager_error_response = $this->getSystemInfo('trigger_cancelwager_error_response', 0);
        $this->trigger_appendwagerresult_error_response = $this->getSystemInfo('trigger_appendwagerresult_error_response', 0);
        $this->trigger_endwager_error_response = $this->getSystemInfo('trigger_endwager_error_response', 0);


        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['wager']);

        $this->game_amount_conversion_precision = $this->getSystemInfo('game_amount_conversion_precision', 2);
    }

    public function getCashierUrl($params = ""){
        if($this->use_referrer){
            $path = $this->getSystemInfo('cashier_redirect_path', '');
            $url = trim(@$_SERVER['HTTP_REFERER'],'/').$path;
        }else{
            $url = $this->getSystemInfo('cashier_redirect_url', '');
        }
        
        if (isset($params['cashier_link'])) {
            $url = $params['cashier_link'];
        }

        return $url;
    }

    public function buildUrl($url, $data){                
        $data = http_build_query($data);
        $url = $url."?".$data;
        return $url;
    }

    public function getPlatformCode(){
        return YGG_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function getCountry(){
        return $this->country;
    }

    public function getOrganization(){
        return $this->organization;
    }

    public function getTopOrganization(){
        return $this->getSystemInfo('topOrg', null);
    }    

	public function getPlayerBalance($player_id){			
		$get_bal_req = $this->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("YGG SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}	
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("YGG SEAMLESS: (depositToGame)");

        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for yggdrasil seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for yggdrasil seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("YGG SEAMLESS: (depositToGame)");

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
        $this->utils->debug_log("YGG SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function queryPlayerBalance($playerName){
        $this->utils->debug_log("YGG SEAMLESS: (queryPlayerBalance)");

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function queryTransaction($transactionId, $extra=null) {
        return $this->returnUnimplemented();
    }

    public function queryForwardGame($playerName, $extra) {
        $this->utils->debug_log("YGG SEAMLESS: (queryForwardGame)",$playerName, $extra);

        
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : 'real';

        $locale=$this->getLauncherLanguage($extra['language']);

        $channel = isset($extra['is_mobile'])&&$extra['is_mobile']==true?'mobile':'pc';

        $homeUrl = null;

        if (isset($extra['home_link']) && !empty($extra['home_link'])) {
            $homeUrl = $extra['home_link'];
        }

        if (array_key_exists("extra", $extra)) {
            if(isset($extra['extra']['t1_lobby_url'])) {
                $homeUrl = $extra['extra']['t1_lobby_url'];
            }
		}

        if(empty($homeUrl)){
            $homeUrl = $this->getHomeLink();
        }

        if(empty($homeUrl)){
            $homeUrl = $this->getSystemInfo('homeUrl');
        }

        $params = array(                
            'currency' => $this->getCurrency(),
            'lang' => $locale,
            'gameid' => $game_code,
            'org' => $this->getOrganization(),
            'channel'=>$channel,
            'home'=>$homeUrl,
            'fullscreen'=>$this->getSystemInfo('launch_in_fullscreen', 'yes'),
        );

        #removes home url if disable_home_link is set to TRUE
        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['home']);
        }

        if(in_array($game_mode, ['trial', 'demo', 'fun'])){            
            $params['org'] = $this->game_launch_url_demo_org;
            $url = $this->buildUrl($this->game_launch_url_demo, $params);
            return ['success'=>true, 'url'=>$url];
        }

        $locale=$this->getLauncherLanguage($extra['language']);

        $channel = isset($extra['is_mobile'])&&$extra['is_mobile']==true?'mobile':'pc';

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getPlayerTokenByUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $params['loginname'] = $gameUsername;
        $params['key'] = $token;

        $this->last_response_result_id=null;
        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId, 'url' => null];
        $this->utils->debug_log("YGG SEAMLESS: (processResultForQueryForwardGame)", 'resultArr', $resultArr);

        if($success){
            if(isset($resultArr['data']) && isset($resultArr['data']['launchurl'])){
                $result['url']=$resultArr['data']['launchurl'];
            }else{
                $success=false;
            }
        }

        return [$success, $result];

    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        return $this->returnUnimplemented();
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
                $lang = 'zh_hans'; // chinese
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
            case 'br':
            case 'pt-br':
            case 'pt-BR':
                $lang = 'pt-BR'; // brazil
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
	}

	protected function customHttpCall($ch, $params) {
		
		$headers = ['Content-Type: application/json'];
		switch ($this->method){
			case 'POST':
                $headers = ['Content-Type: application/x-www-form-urlencoded'];
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
			case 'GET':

			break;
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}

    public function generateUrl($apiName, $params) {

        $apiUri = '';
        if(array_key_exists($apiName,self::URI_MAP)){
            $apiUri = self::URI_MAP[$apiName];
        }

        if (self::METHOD_POST == $this->method) {
            $url = $this->api_url .'/'. $apiUri;
            if($apiName==self::API_queryForwardGame){
                $url = $this->game_launch_url;
            }
        }else{
            $url = $this->api_url .'/'. $apiUri;
            $url = $this->buildUrl($url, $params);
        }

        $this->CI->utils->debug_log('apiName', $apiName, 'url', $url, 'params', $params);        
        return $url;
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {        
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $this->enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`end_at` >= ? AND `original`.`end_at` <= ?';
        }
        $this->CI->utils->debug_log('YGG SEAMLESS sqlTime', $sqlTime, 'table', $table);
        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.start_at', 'original.updated_at', 'original.status', 'gd.game_name'));

        //result amount = win - bet
        $sql = <<<EOD
SELECT
	original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.game_id,
    original.game_name trans_game_name,
	original.start_at as start_at,
    original.end_at as end_at,
    original.start_at as bet_at,
    original.updated_at as updated_at,
    original.player_id as player_id,
    original.round_id as round_id,
    original.transaction_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    original.amount amount,
    original.bet_amount bet_amount,
    original.win_amount win_amount,
    (original.win_amount-original.bet_amount) result_amount,
    game_provider_auth.login_name game_username,
    original.extra_info,   
    original.status, 
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,    
    original.game_name as game_name,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$table} as original
JOIN game_provider_auth ON game_provider_auth.player_id = original.player_id AND game_provider_auth.game_provider_id=?
LEFT JOIN game_description as gd ON original.game_id = gd.game_code AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE 
{$sqlTime}
 AND original.transaction_type<>'cancelwager';
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('YGG SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        

        $prevTableData = $finalData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();
        if(($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table) || $checkOtherTable){
            $prevTable = $this->getTransactionsPreviousTable(); 
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                   
        }
        $finalData = array_merge($currentTableData, $prevTableData);        
        
        return $finalData;
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $betDetails = $extra = [];

        if(!isset($row['md5_sum']) || empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
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
                'player_username' => $row['game_username']
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
                'start_at' => $row['bet_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getStatus($row),
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $betDetails,            
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function getStatus($row){
        $status = Game_logs::STATUS_UNSETTLED;
        if($row['status']==Game_logs::STATUS_SETTLED) {
            $status = Game_logs::STATUS_SETTLED;
        }elseif($row['status']==Game_logs::STATUS_CANCELLED) {
            $status = Game_logs::STATUS_CANCELLED;
        }
        return $status;
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = $this->getStatus($row);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {        
        $gameName = $row['trans_game_name'];

		$game_description_id = null;
		$external_game_id = $gameName;
        $extra = array('game_code' => $row['game_id'],'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($gameUsername);
        return array('success' => $success);
    }
    public function unblockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($gameUsername);
        return array('success' => $success);
    }   

    public function changePassword($playerName, $oldPassword, $newPassword){
        return ['success'=>$this->changePasswordInDB($playerName, $newPassword)];
    }

    ##### SEAMLESS BALANCE HISTORY METHODS

    public function isBlocked($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        return $this->isBlockedUsernameInDB($gameUsername);
    }

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("YGG SEAMLESS: (queryPlayerBalance)");

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function getTransactionsTable($monthStr = null){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        if(empty($monthStr)){
            $date=new DateTime();
            $monthStr=$date->format('Ym');
        }
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

		$tableName='ygg_seamless_wallet_transactions_'.$yearMonthStr;
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{

                $this->CI->load->dbforge();

                $fields = array(
                    'id' => [
                        'type' => 'BIGINT',
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'game_platform_id' => [
                        'type' => 'INT',
                        'constraint' => '6'
                    ],
                    'amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'bet_amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'win_amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'cancelled_amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'result_amount' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'before_balance' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'after_balance' => [
                        'type' => 'DOUBLE',
                        'null' => true
                    ],
                    'player_id' => [
                        'type' => 'INT',
                        'constraint' => '12',
                        'null' => true
                    ],
                    'game_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true
                    ],
                    'game_name' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true
                    ],
                    'transaction_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'wallet_adjustment_mode' => [
                        'type' => 'VARCHAR',
                        'constraint' => '25',
                        'null' => true
                    ],
                    'status' => [
                        'type' => 'INT',
                        'constraint' => '11',
                        'null' => true
                    ],
                    'response_result_id' => [
                        'type' => 'INT',
                        'constraint' => '11',
                        'null' => true
                    ],
                    'external_uniqueid' => [
                        'type' => 'VARCHAR',
                        'constraint' => '150',
                        'null' => true
                    ],
                    'round_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '150',
                        'null' => true
                    ],
                    'transaction_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '150',
                        'null' => true
                    ],
                    'extra_info' => [
                        'type' => 'JSON',
                        'null' => true
                    ],
                    'start_at' => [
                        'type' => 'DATETIME',
                        'null' => true
                    ],
                    'end_at' => [
                        'type' => 'DATETIME',
                        'null' => true
                    ],
                    'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                        'null' => false,
                    ],
                    'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                        'null' => false,
                    ],
                    'cost' => [
                        'type' => 'INT',
                        'constraint' => '5',
                        'null' => true
                    ]
                );

                $this->CI->dbforge->add_field($fields);
                $this->CI->dbforge->add_key('id', TRUE);
                $this->CI->dbforge->create_table($tableName);
                # Add Index
                $this->CI->load->model('player_model');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_player_id','player_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_start_at','start_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_end_at','end_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_updated_at','updated_at');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_transaction_type','transaction_type');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_round_id','round_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_game_id','game_id');
                $this->CI->player_model->addIndex($tableName,'idx_seamlesstransaction_status','status');
                $this->CI->player_model->addUniqueIndex($tableName, 'idx_seamlesstransaction_external_uniqueid', 'external_uniqueid');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
			}
		}

		return $tableName;
	}

    public function getTransactionsPreviousTable(){
        $d = new DateTime('-1 month');                 
        $monthStr = $d->format('Ym');

        if(!empty($this->previous_transactions_table)){
            return $this->previous_transactions_table;
        }
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr);        
    }

	public function debitCreditAmountToWallet($params, $request, &$previousBalance, &$afterBalance, $mode){        

        if(empty($this->request)){
            $this->request = $request;
        }

		$this->CI->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);
        $this->CI->load->model(array('ygg_transactions'));
        $this->CI->ygg_transactions->tableName = $this->getTransactionsTable();
        $currentTableName = $this->getTransactionsTable();
		//initialize params
		$player_id			= $params['player_id'];		

		//initialize response
		$success = false;	
		$insufficientBalance = false;
		$isAlreadyExists = false;		
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];
        $trans_type = $params['transaction_type'];
        $prevTranstable = $this->getTransactionsPreviousTable();
        $betExist = null;

		if($params['transaction_type']==self::TRANSTYPE_WAGER){
			
		}elseif($params['transaction_type']==self::TRANSTYPE_CANCEL_WAGER){			
			$flagrefunded = true;		
		}elseif($params['transaction_type']==self::TRANSTYPE_APPEND_WAGER_RESULT){
			
		}elseif($params['transaction_type']==self::TRANSTYPE_END_WAGER){
			
		}elseif($params['transaction_type']==self::TRANSTYPE_CAMPAIGN_PAYOUT){
			
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}        
        
		$uniqueId           = $params['external_uniqueid']; 
        
        $checkOtherTable = $this->checkOtherTransactionTable();
        if($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table){
            $checkOtherTable = true;
        }

        //get and process balance
        $get_balance = $this->getPlayerBalance($player_id);
        if($get_balance!==false){
            $afterBalance = $previousBalance = $get_balance;
        }else{				
            $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
            return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        // get all related transaction under the round
        $check_bet_params = ['round_id'=>strval($params['round_id']),                 
        'player_id'=>$player_id];
        $prevRoundData = [];
        $currentRoundData = $this->CI->ygg_transactions->getRoundData($currentTableName, $check_bet_params);

        if($checkOtherTable){                    
            $prevRoundData = $this->CI->ygg_transactions->getRoundData($prevTranstable, $check_bet_params);
        }

        $roundData = array_merge($currentRoundData, $prevRoundData);
        $endWagerData =  $cancelData = $wagerData = null;
        foreach($roundData as $rowData){
            if($rowData['transaction_type']==self::TRANSTYPE_CANCEL_WAGER){
                $cancelData = $rowData;
            }

            if($rowData['transaction_type']==self::TRANSTYPE_WAGER){
                $betExist = $wagerData = $rowData;
            }

            if($rowData['transaction_type']==self::TRANSTYPE_END_WAGER){
                $endWagerData = $rowData;
            }

            if(strcmp($uniqueId,$rowData['external_uniqueid'])==0){
                $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already in current rowData", $rowData, 'uniqueId', $uniqueId);
                $isAlreadyExists = true;
            }
        }

        $this->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) roundData", 
        'roundData', $roundData, 
        'params',$params);

        //check if already exist        
        if($isAlreadyExists){
            $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already in current transactions", $isAlreadyExists, 
            'params', $params,
            'uniqueId', $uniqueId,
            'currentTableName', $currentTableName);
            $isAlreadyExists = true;					
            $afterBalance = $previousBalance;
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        //check endwager if already processed by cancelwager
        if (!empty($cancelData) && $params['transaction_type'] == self::TRANSTYPE_END_WAGER) {
            $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) transactions already processed cancelwager", $isAlreadyExists, 
            'params', $params,
            'uniqueId', $uniqueId,
            'currentTableName', $currentTableName);
            $additionalResponse['isAlreadyProcessedByCancelwager'] = true;
            $afterBalance = $previousBalance;
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        //check cancelwager if already processed by endwager
        if (!empty($endWagerData) && $params['transaction_type'] == self::TRANSTYPE_CANCEL_WAGER) {
            $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) transactions already processed endwager", $isAlreadyExists, 
            'params', $params,
            'uniqueId', $uniqueId,
            'currentTableName', $currentTableName);
            $additionalResponse['isAlreadyProcessedByEndwager'] = true;
            $afterBalance = $previousBalance;
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        //check if cancelled already recorded in the DB
        if(!empty($cancelData)&&$params['transaction_type']<>self::TRANSTYPE_CANCEL_WAGER){
            $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) transactions already has cancel record", $isAlreadyExists, 
            'params', $params,
            'uniqueId', $uniqueId,
            'currentTableName', $currentTableName);            
            $additionalResponse['isCancelledAlready']=true;				
            $afterBalance = $previousBalance;
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        //if cancel and bet not exist
        if(empty($betExist) && $params['transaction_type']==self::TRANSTYPE_CANCEL_WAGER && $params['round_id']<>0){
            $this->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist FOR CANCELWAGER SET TRUE", 
            'betExist', $betExist, 
            'params',$params, 
            'check_bet_params', $check_bet_params,
            'prevTranstable', $prevTranstable);
            $additionalResponse['betExist']=false;		
            $afterBalance = $previousBalance;
            
            #save transactions as cancelled
            $isCancelAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
            if($isCancelAdded===false){
                $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isCancelAdded, $params);
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }
            return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        //if cancel get amount to add from wager data
        if(!empty($betExist) && $params['transaction_type']==self::TRANSTYPE_CANCEL_WAGER){
            $params['amount'] = $betExist['amount'];
            $params['cancelled_amount'] = $betExist['amount'];
            $params['bet_amount'] = $betExist['amount'];
        }

        //bet exist checking, before processing payouts, to make sure payout only with valid bet
        if(($params['transaction_type']==self::TRANSTYPE_APPEND_WAGER_RESULT ||         
        $params['transaction_type']==self::TRANSTYPE_END_WAGER || 
        $params['transaction_type']==self::TRANSTYPE_APPEND_WAGER_RESULT) && $params['round_id']<>0
        ){            
            if(empty($betExist)){
                $additionalResponse['betExist']=false;
                $this->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist", 
                'betExist', $betExist, 
                'params',$params, 
                'check_bet_params', $check_bet_params,
                'prevTranstable', $prevTranstable);
                $afterBalance = $previousBalance;
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }else{
                if(isset($betExist['status']) && $betExist['status']==Game_logs::STATUS_CANCELLED){
                    $additionalResponse['isAlreadyProcessedByCancelwager'] = true;
                    return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
            }	            

            $additionalResponse['betExist']=true;	
        }
        		
		$amount = abs($params['amount']);

		if($amount<>0){

            //compute balance
            $afterBalance = $previousBalance = $get_balance;
            if($mode=='debit'){
                $afterBalance = $afterBalance - $amount;
            }else{
                $afterBalance = $afterBalance + $amount;
            }

			if($params['transaction_type']==self::TRANSTYPE_CANCEL_WAGER){	
				$flagTransactionRefundedResp = $this->CI->ygg_transactions->flagRoundCancelled($betExist['round_id']);

                if($checkOtherTable && !$flagTransactionRefundedResp){                                        
                    $this->CI->ygg_transactions->flagRoundCancelled($betExist['round_id'], $prevTranstable);
                }
			}

			if($params['transaction_type']==self::TRANSTYPE_END_WAGER){	
				$flagTransactionRefundedResp = $this->CI->ygg_transactions->flagRoundSettled($betExist['round_id']);

                if($checkOtherTable && !$flagTransactionRefundedResp){                                        
                    $this->CI->ygg_transactions->flagRoundSettled($betExist['round_id'], $prevTranstable);
                }
			}

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);				
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);

			if($isAdded===false){
				$this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}	

			$success = $this->transferGameWallet($player_id, $this->getPlatformCode(), $mode, $amount);

			if(!$success){
				$this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
			}

		}else{
			$get_balance = $this->getPlayerBalance($player_id);
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				$success = true;

				//insert transaction
				$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
                if($isAdded===false){
                    $this->utils->error_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
    
                //rollback amount because it already been processed
                if($isAdded==0){
                    $this->utils->debug_log("YGG SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
                    $isAlreadyExists = true;					
                    $afterBalance = $previousBalance;
                    return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }else{
                    $isTransactionAdded = true;
                }
			}else{
				$success = false;
			}
		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded);
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);		
        $tableName = $this->getTransactionsTable();
        $this->CI->ygg_transactions->setTableName($tableName);        
		return $this->CI->ygg_transactions->insertIgnoreRow($trans_record);        		
	}


	public function makeTransactionRecord($raw_data){
		$data = [];		
        $data['game_platform_id'] 	    = $this->getPlatformCode();		
        $data['amount'] 			    = isset($raw_data['amount'])?$raw_data['amount']:null;
        $data['bet_amount'] 		    = isset($raw_data['bet_amount'])?$raw_data['bet_amount']:null;
        $data['win_amount'] 		    = isset($raw_data['win_amount'])?$raw_data['win_amount']:null;
        $data['cancelled_amount'] 		= isset($raw_data['cancelled_amount'])?$raw_data['cancelled_amount']:null;
        $data['result_amount'] 		    = isset($raw_data['result_amount'])?$raw_data['result_amount']:null;
        $data['before_balance'] 	    = isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		    = isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;	
        $data['player_id'] 			    = isset($raw_data['player_id'])?$raw_data['player_id']:null;
        $data['transaction_type'] 		= isset($raw_data['transaction_type'])?$raw_data['transaction_type']:null;
        $data['game_id'] 			    = isset($raw_data['game_id'])?$raw_data['game_id']:null;
        $data['game_name'] 			    = isset($raw_data['game_name'])?$raw_data['game_name']:null;
        $data['wallet_adjustment_mode'] = isset($raw_data['wallet_adjustment_mode'])?$raw_data['wallet_adjustment_mode']:null;
        $data['status'] 			    = isset($raw_data['status'])?$raw_data['status']:null;
        $data['response_result_id']     = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;	
        $data['external_uniqueid'] 	    = $raw_data['external_uniqueid'];
        $data['round_id'] 			    = isset($raw_data['round_id'])?$raw_data['round_id']:null;
        $data['transaction_id'] 	    = isset($raw_data['transaction_id'])?$raw_data['transaction_id']:null;
        $data['extra_info'] 		    = isset($raw_data['extra_info'])?$raw_data['extra_info']:json_encode([]);
        if(is_array($data['extra_info'])){
            $data['extra_info'] = json_encode($data['extra_info']);
        }
        $data['start_at'] 			    = isset($raw_data['start_at'])?$raw_data['start_at']:null;
        $data['end_at'] 			    = isset($raw_data['end_at'])?$raw_data['end_at']:null;
        $data['cost'] 		            = intval($this->utils->getExecutionTimeToNow()*1000);
		
		return $data;
	}
    
    public function queryTransactionByDateTimeGetData($table, $startDate, $endDate){
        
$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.game_platform_id as game_platform_id,
t.start_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info,
t.game_id game_code
FROM {$table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];
        
                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
    }


    public function queryTransactionByDateTime($startDate, $endDate){
        $date = new DateTime($startDate);
        $monthStr = $date->format('Ym');
        $transactionTable = $this->getTransactionsTable();
        $currentTableData = $this->queryTransactionByDateTimeGetData($transactionTable, $startDate, $endDate);

        $prevTableData = $finalData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();
        if(($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table) || $checkOtherTable){
            $prevTable = $this->getTransactionsPreviousTable(); 
            $prevTableData = $this->queryTransactionByDateTimeGetData($prevTable, $startDate, $endDate);                   
        }
        $finalData = array_merge($currentTableData, $prevTableData);        
        
        return $finalData;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
        
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = array();
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $transaction['game_platform_id'];
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

	public function transferGameWallet($player_id, $game_platform_id, $mode, $amount){
		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
			$success = $this->CI->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);	
		}elseif($mode=='credit'){
			$success = $this->CI->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
		}

		return $success;
	}

}//end of class
