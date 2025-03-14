<?php
/**
 * JUMBO Seamless game integration
 * OGP-27308
 *
 * @author  Kristallynn Tolentino
 *
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_jumbo_seamless extends Abstract_game_api {

    const API_trialGame = 47; 

    const URI_MAP = array(
        self::API_checkLoginToken => 21,
        self::API_queryDemoGame => 47,
        self::API_queryGameListFromGameProvider => 49,
        // self::API_syncLostAndFound => 64,
        self::API_syncLostAndFound => 29,
    );

    const MD5_FIELDS_FOR_MERGE = [
        'game_status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'amount',
        'result_amount',
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCELLED = 'cancelled';
    const TRANSACTION_REFUND = 'refunded';

    protected $api_url = null;

    public $dc;
    public $iv;
    public $key;
    public $agent;
    public $original_transaction_table_name;
    public $enable_merging_rows;
    public $use_monthly_transactions_table;
    public $allowed_day_to_check_monthly_table; 
    public $force_check_prev_table_for_sync;

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'http://api.jygrq.com/apiRequest.do?');
        $this->lang  = $this->getSystemInfo('lang', 'en');

        $this->currency = $this->getSystemInfo('currency', 'USD');
        $this->dc = $this->getSystemInfo('dc', 'TOSW');
        $this->iv = $this->getSystemInfo('token_iv', 'fd0829e541472edd');
        $this->key = $this->getSystemInfo('token_key', '1bb4e28a13285cc6');
        $this->agent = $this->getSystemInfo('agent', 'c042apbusd');

        $this->original_transaction_table_name = 'jumbo_seamless_wallet_transactions';

        $this->use_game_gtype = $this->getSystemInfo('use_game_gtype', false);
        $this->post_json = false;
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->allowed_day_to_check_monthly_table = $this->getSystemInfo('allowed_day_to_check_monthly_table', '1');
        $this->force_check_prev_table_for_sync = $this->getSystemInfo('force_check_prev_table_for_sync', false);

        $this->fix_username_limit      = $this->getSystemInfo('fix_username_limit',true);
		$this->minimum_user_length      = $this->getSystemInfo('minimum_user_length',5);
        $this->maximum_user_length      = $this->getSystemInfo('maximum_user_length',50);
        

        $this->enable_mock_cancel_bet = $this->getSystemInfo('enable_mock_cancel_bet', false);
        $this->enable_mock_cancel_player_list = $this->getSystemInfo('enable_mock_cancel_player_list', []);
        $this->game_types = $this->getSystemInfo('game_types', []);

        $this->syncOriginalInterval = $this->getSystemInfo('syncOriginalInterval', '+5 minutes');
        $this->enable_sync_original = $this->getSystemInfo('enable_sync_original', false);
        $this->jumb_game_logs = 'jumb_game_logs';
    }

    // public function getTransactionsTable(){
    //     return $this->original_transaction_table_name;
    // }
    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table_name;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table_name;
        }

        $tableName=$this->original_transaction_table_name.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like {$this->original_transaction_table_name}");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }
    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return JUMBO_SEAMLESS_GAME_API;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function generateUrl($apiName, $params) {
        if($apiName == self::API_triggerInternalRefundRound){
            $id = $this->getPlatformCode();
            $url = $this->CI->utils->getServerProtocol(). "://".$this->CI->utils->getSystemHost('admin')."/jumbo_seamless_service_api/index/{$id}/rollback";
            return $url;
        }
        $url = $this->api_url.'dc='.$params['dc']. '&x=' . urlencode($params['x']);
        $this->CI->utils->debug_log("JUMBO-url:", $url);

        return $url;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, true);
        if($this->post_json){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params)); 
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public static function encrypt($data, $key, $iv)
    {
        $data = self::padString($data);
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_NO_PADDING, $iv);
        $encrypted = base64_encode($encrypted);
        $encrypted = str_replace(array('+','/','=') , array('-','_','') , $encrypted);
        return $encrypted;
    }

    private static function padString($source)
    {
        $paddingChar = ' ';
        $size = 16;
        $x = strlen($source) % $size;
        $padLength = $size - $x;
        for ($i = 0;$i < $padLength;$i++)
        {
            $source .= $paddingChar;
        }
        return $source;
    }

    public static function decrypt($data, $key, $iv)
    {
        $data = str_replace(array('-','_') , array('+','/') , $data);
        $data = base64_decode($data);
        $decrypted = openssl_decrypt($data, 'AES-128-CBC', $key, OPENSSL_NO_PADDING, $iv);
        return utf8_encode(trim($decrypted));
    }

    protected function jumb_now() {
        return round(microtime(true)*1000);
    }

    protected function processResultBoolean($responseResultId, $resultArr, $player_name = null, $apiName = null) {
        $this->CI->utils->debug_log("apiName ================", $apiName);
        $success = false;

        # status 0000 for success and status 7602 for account already exists
        if(isset($resultArr['status']) && ($resultArr['status'] == '0000' || $resultArr['status'] == '7602')) {
            $success = true;
        }

        # status 0000 for success, status 7501 for player not exist (will auto create player)
        if($apiName == "IsPlayerExist" && $resultArr['status'] == '7501'){
            $this->CI->utils->debug_log('if Player is not exist it this will auto create player');
            $success = true;
        }

        if($resultArr['status'] == '8006'){
            $this->CI->utils->debug_log('Jumb Sync Original Game Logs: No available game history at this date range');
            $success = true;
        }

        if(!$success){
           $this->setResponseResultToError($responseResultId);
           $this->CI->utils->debug_log('JUMB got error ======================================>', $responseResultId, 'playerName', $player_name, 'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $extra = [
			'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length
        ];

        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for JUMBO Seamless";
        if($return){
            $success = true;
            $message = "Successfull create account for JUMBO Seamless";
        }

        return array("success" => $success, "message" => $message);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $useReadonly = true;
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode(), $useReadonly);

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
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

    public function queryForwardGame($playerName = null, $extra=null) {
        $result = $this->getAccessToken($playerName,$extra);
        $data = [
            'url' => '',
            'success' => false
        ];

        if ($result['success']) {
            if(isset($result['launcher_url'])){
                $url = $result['launcher_url']; // $this->web_url . '?x=' . $this->access_token;
                $data = [
                    'url' => $url,
                    'success' => true
                ];
            }else if(isset($result['access_token'])){
                $url = $this->web_url . '?x=' . $result['access_token'];
                $data = [
                    'url' => $url,
                    'success' => true
                ];
            }
        }

        return $data;
    }

    public function getAccessToken($playerName = null, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $balance = $this->queryPlayerBalance($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAccessToken',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $api_action = (isset($extra['game_mode']) && $extra['game_mode'] == 'real') ? self::URI_MAP[self::API_checkLoginToken] : self::URI_MAP[self::API_queryDemoGame];

        // check if game type is empty or isset

        if(!isset($extra['game_type']) || empty($extra['game_type'])) {
            $this->CI->load->model('game_description_model');

            $gameDesc = $this->CI->game_description_model->getGameDescByGameCode($extra['game_code'], $this->getPlatformCode());
            $game_type_code = isset($gameDesc["game_type_code"]) ? $gameDesc["game_type_code"] : "";

            $gType = $this->getLauncherGameType($game_type_code);
        } else {
            $gType = $this->getLauncherGameType($extra['game_type']);
        }
        
        $jumb_params = array(
            'action'    => $api_action,    //  
            'ts'        => $this->jumb_now() ,
            'parent'    => $this->agent,
            'uid'       => $gameUsername ,
            'balance'   => $balance['balance'],
            'lang'      => $this->getLauncherLanguage($extra['language']) ,
            'gType'     => $gType, #'0' , # 0 slot , 7 Fishing machine
            'mType'    => $extra['game_code'],
            'windowMode'=> 2 # 1 - Include game hall, 2 - does not contain the game hall, hide the close button in the game gType and mType are required fields.
        );

        $gameMode = isset($extra['game_mode']) ? $extra['game_mode']:null;
		if(in_array($gameMode, $this->demo_game_identifier)){
            unset($jumb_params['uid']);
        }

        if($this->use_game_gtype){
            // get gtype from game
            $this->CI->load->model('game_description_model');
            $external_game_id = $extra['game_code'];
            $gameDetails = $this->CI->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->getPlatformCode(),$external_game_id, true);
            
            $json=$this->utils->decodeJson($gameDetails['attributes']);
            if(!empty($json) && isset($json['gType']) && !empty($json['gType'])){
                $jumb_params['gType']=$json['gType'];
            }
            $this->utils->debug_log("JUMBO SEAMLESS jumb_params bermar ============================>", $jumb_params, 'json', $json, 'gameDetails', $gameDetails, 'plain', $gameDetails['attributes']);
        }

        $encrypted = $this->encrypt(json_encode($jumb_params), $this->key, $this->iv);

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $encrypted
        );

        $this->utils->debug_log("JUMBO SEAMLESS jumb_params ============================>", $jumb_params);
        $this->utils->debug_log("JUMBo SEAMLESS ecrypted params ============================>", $params);
        
        if($extra['game_mode'] != 'real'){
            return $this->callApi(self::API_queryDemoGame, $params, $context);
        } else {
            return $this->callApi(self::API_queryForwardGame, $params, $context);
        }

    }


    public function processResultForGetAccessToken($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,null);
        $this->utils->debug_log("JUMB getAccessToken ============================>", $resultArr);

        $result=['response_result_id'=>$responseResultId];

        if ($success) {
            if(isset($resultArr['path'])){
                $result['launcher_url']=$resultArr['path'];
            }else if(isset($resultArr['x'])){
                $result['access_token']=$resultArr['x'];
            }
        }

        return [$success,$result];
    }


    public function queryGameListFromGameProvider($extra=null) {
        $language = $this->lang; 

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $api_action = self::URI_MAP[self::API_queryGameListFromGameProvider];

        
        $jumb_params = array(
            'action'    => $api_action,    //  
            'ts'        => $this->jumb_now() ,
            'parent'    => $this->agent,
            'lang'      => $this->getLauncherLanguage($language) ,
        );

        $encrypted = $this->encrypt(json_encode($jumb_params), $this->key, $this->iv);

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $encrypted
        );

        $this->utils->debug_log("JUMBO SEAMLESS jumb_params ============================>", $jumb_params);
        $this->utils->debug_log("JUMBo SEAMLESS ecrypted params ============================>", $params);
        
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }


    public function processResultForQueryGameListFromGameProvider($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,null);
        $this->utils->debug_log("JUMB queryGameListFromGameProvider ============================>", $resultArr);

        return [$success,$resultArr];
    }

    public function getLauncherGameType($game_type){
        $new_gameType='';
        switch ($game_type) {
            case 'slots':
                $new_gameType = '0';
                break;
            case 'fishing':
            case 'fishing_game':
                $new_gameType = '7';
                break;
            case 'arcade':
                $new_gameType = '9';
                break;
            case 'table_and_cards':
            case 'card_games':
                $new_gameType = '18';
                break;
            case 'lottery':
                $new_gameType = '12';
                break;
            default:
                $new_gameType = '0'; // by default slots
                break;
        }
        return $new_gameType;
    }

    public function getLauncherLanguage($language){
        $lang='';
        $language = strtolower($language);
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en-US':
                $lang = 'en'; // english
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'ch'; // chinese
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'in'; // indonesia
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vn'; // vietnamese
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_PORTUGUESE :
            case 'pt':
            case 'pt-pt':
            case 'pt-br':
            case 'pt-BR':
                $lang = 'pt';
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

    public function syncOriginalGameLogs_old($token = false) {
        return $this->returnUnimplemented();
        

        //for local syncing
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

        $action = self::URI_MAP[self::API_syncLostAndFound];
        $starttime = $dateTimeFrom->format('d-m-Y H:i:00'); 
        $endtime = $dateTimeTo->format('d-m-Y H:i:00');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetJumbGamelogs',
            'starttime' => $starttime,
            'endtime' => $endtime,
        );

        $jumb_params = [
            'action' => $action,
            'ts' => $this->jumb_now(),
            'parent' => $this->agent,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'gTypes' => $this->game_types,
        ];

        $params = [
            'dc' => $this->dc,
            'x' => $this->encrypt(json_encode($jumb_params), $this->key, $this->iv)
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' request params ---------->', $jumb_params, $params);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForGetJumbGamelogs($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $result = $this->getResultJsonFromParams($params);
        $data = [];
        if(!empty($result)){
            $data = isset($result['data']) ? $result['data']: [];
            if(!empty($data)){
                foreach ($data as $key => $datai) {
                    $data[$key] = array(
                        "action"=> 8,
                        "transferId"=> $datai['transferId'],
                        "historyId"=> $datai['historyId'],
                        "uid"=> $datai['playerId'],
                        "reportDate"=> $datai['gameDate'],
                        "gameDate"=> $datai['gameDate'],
                        "bet"=> $datai['bet'],
                        "win"=> $datai['win'],
                        "netWin"=> $datai['total'],
                        "currency"=> $datai['currency'],
                        "lastModifyTime"=> $datai['lastModifyTime'],
                        "denom"=> $datai['denom'],
                        "systemTakeWin"=> @$datai['systemTakeWin'],
                        "gType"=> $datai['gType'],
                        "mType"=> $datai['mtype']
                    );
                };
            }
        }
        echo json_encode($data);
    }

    public function syncOriginalGameLogs($token) {
        if(!$this->enable_sync_original){
            return $this->returnUnimplemented();
        }
    
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

        $action = self::URI_MAP[self::API_syncLostAndFound];

		$records_count = 0;
        $this->CI->utils->loopDateTimeStartEnd($dateTimeFrom, $dateTimeTo, $this->syncOriginalInterval, function($dateTimeFrom, $dateTimeTo) use(&$records_count, &$action) {
            $starttime = $dateTimeFrom->format('d-m-Y H:i:00'); 
            $endtime = $dateTimeTo->format('d-m-Y H:i:00');

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'starttime' => $starttime,
				'endtime' => $endtime
			);

			$done = false;

			while(!$done){
				$jumb_params = [
                    'action' => $action,
                    'ts' => $this->jumb_now(),
                    'parent' => $this->agent,
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'gTypes' => $this->game_types,
                ];
        
                $params = [
                    'dc' => $this->dc,
                    'x' => $this->encrypt(json_encode($jumb_params), $this->key, $this->iv)
                ];
        
                $this->CI->utils->debug_log(__METHOD__ . ' request params ---------->', $jumb_params, $params);
        
                $records = $this->callApi(self::API_syncGameRecords, $params, $context);

				$data_count 	= isset($records['data_count']) ? $records['data_count'] : 0;
				if($data_count == 0 ){
					$done = true;
				}
				$records_count  += $data_count;
			}
			
			return ['success' => true];
        });

		$result = [
			'success' => true,
			'records_count' => $records_count,
		];
        return $result;
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model('original_game_logs_model');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,null,true);
		$result = array('data_count'=>0);

		$this->CI->utils->debug_log('jumbo (processResultForSyncOriginalGameLogs)' , $success, $resultArr,$this->jumb_game_logs);

		$gameRecords = isset($resultArr['data']) ? $resultArr['data'] : [];

		if($success && !empty($gameRecords)){
			$extra = ['response_result_id'=>$responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->jumb_game_logs,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                ['bet','win','jackpot', 'beforeBalance','afterBalance'],
                'md5_sum',
                'id',
                ['bet','win','jackpot', 'beforeBalance','afterBalance']
            );
			$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);

            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
		}

		$this->CI->utils->debug_log('processResultForSyncOriginalGameLogs--',$result, $resultArr);

		return array($success, $result);
	}


    private function rebuildGameRecords(&$gameRecords,$extra){
		$this->CI->utils->debug_log('jumbo (rebuildGameRecords)', $gameRecords,$gameRecords, $extra);
		
		$new_gameRecords = array();	
		
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$new_gameRecords[$index]['Username'] 		= isset($record['playerId']) ? $record['playerId'] : null;
				$new_gameRecords[$index]['seqNo'] 		= isset($record['roundSeqNo']) ? $record['roundSeqNo'] : null;
				$new_gameRecords[$index]['mtype'] 		= isset($record['mtype']) ? $record['mtype'] : null;
				$new_gameRecords[$index]['gType'] 		= isset($record['gType']) ? $record['gType'] : null;
				$new_gameRecords[$index]['gameDate'] 		= isset($record['gameDate']) ? $record['gameDate'] : null;
				$new_gameRecords[$index]['bet'] 		= isset($record['bet']) ? $record['bet'] : null;
				$new_gameRecords[$index]['win'] 		= isset($record['win']) ? $record['win'] : null;
				$new_gameRecords[$index]['total'] 		= isset($record['total']) ? $record['total'] : null;
				$new_gameRecords[$index]['currency'] 		= isset($record['currency']) ? $record['currency'] : null;
				$new_gameRecords[$index]['jackpot'] 		= isset($record['jackpot']) ? $record['jackpot'] : null;
				$new_gameRecords[$index]['jackpotContribute'] 		= isset($record['jackpotContribute']) ? $record['jackpotContribute'] : null;
				$new_gameRecords[$index]['denom'] 		= isset($record['denom']) ? $record['denom'] : null;
				$new_gameRecords[$index]['lastModifyTime'] 		= isset($record['lastModifyTime']) ? $record['lastModifyTime'] : null;
				$new_gameRecords[$index]['gameName'] 		= isset($record['gameName']) ? $record['gameName'] : null;
				$new_gameRecords[$index]['playerIp'] 		= isset($record['playerIp']) ? $record['playerIp'] : null;
				$new_gameRecords[$index]['clientType'] 		= isset($record['clientType']) ? $record['clientType'] : null;
				$new_gameRecords[$index]['hasFreegame'] 		= isset($record['hasFreegame']) ? $record['hasFreegame'] : null;
				$new_gameRecords[$index]['hasGamble'] 		= isset($record['hasGamble']) ? $record['hasGamble'] : null;
				$new_gameRecords[$index]['systemTakeWin'] 		= isset($record['systemTakeWin']) ? $record['systemTakeWin'] : null;
				$new_gameRecords[$index]['beforeBalance'] 		= isset($record['beforeBalance']) ? $record['beforeBalance'] : null;
				$new_gameRecords[$index]['afterBalance'] 		= isset($record['afterBalance']) ? $record['afterBalance'] : null;
				$new_gameRecords[$index]['historyId'] 		= isset($record['historyId']) ? $record['historyId'] : null;
				$new_gameRecords[$index]['response_result_id'] 		= isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
                
				$new_gameRecords[$index]['external_uniqueid']  = isset($record['transferId']) ? $record['transferId'] : null;
                
			}
		}
        $gameRecords = $new_gameRecords;
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->jumb_game_logs, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->jumb_game_logs, $record);
                }
                $dataCount++;
                unset($data);
            }
        }
        return $dataCount;
    }
    ////////////////////////////////////////////

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogsFromMonthlyTable($dateFrom, $dateTo, $useBetTime){
        $months = $this->get_months_in_range($dateFrom, $dateTo, "Ym");
        $results = [];
        if(!empty($months)){
            foreach ($months as $key => $month) {
                $tableName = $this->original_transaction_table_name.'_'.$month;
                if ($this->CI->utils->table_really_exists($tableName)) {
                    $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

                    if($useBetTime) {
                        $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
                    }
                    $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.bet_amount,
    transaction.win_amount,
    transaction.result_amount,
    transaction.amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.game_status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.historyId as round_id,
    transaction.md5_sum,
    transaction.transaction_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.english_name AS game_english_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$tableName} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction_type != "refunded" and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
        ];

                    $monthyResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                    $results = array_merge($results, $monthyResult);
                }
            }
        }

        if ($this->enable_merging_rows) {
            foreach ($results as $key => $result) {
                if ($result['transaction_type'] == 'debit' && $result['game_status'] == 1) {
                    unset($results[$key]);
                }
            }
    
            $results = array_values($results);
        }

        return $results;
    }

     /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        #queryOriginalGameLogsFromMonthlyTable will be handled by switching tableName
        // if($this->use_monthly_transactions_table){
        //     return $this->queryOriginalGameLogsFromMonthlyTable($dateFrom, $dateTo, $use_bet_time);
        // }
        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);    
        $prevTableData = [];

        if(date('j', $this->utils->getTimestampNow()) <= $this->allowed_day_to_check_monthly_table) {
            $this->force_check_prev_table_for_sync = true;
        }

        $checkOtherTable = $this->checkOtherTransactionTable();

        if(($this->force_check_prev_table_for_sync&&$this->use_monthly_transactions_table) || $checkOtherTable){        
            $prevTable = $this->getTransactionsPreviousTable();        
            $this->CI->utils->debug_log("(queryOriginalGameLogs) getting prev month data", 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                           
        }
        return array_merge($currentTableData, $prevTableData);
    }

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.bet_amount,
    transaction.win_amount,
    transaction.result_amount,
    transaction.amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.game_status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.historyId as round_id,
    transaction.md5_sum,
    transaction.transaction_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.english_name as game_english_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction_type != "refunded" and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
        ];

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if ($this->enable_merging_rows) {
            foreach ($results as $key => $result) {
                if ($result['transaction_type'] == 'debit' && $result['game_status'] == 1) {
                    unset($results[$key]);
                }
            }
    
            $results = array_values($results);
        }

        return $results;
    }

    function get_months_in_range($start, $end, $format='Ym') {
        $i = $formatted_i = $start = strtotime($start);
        $formatted_i = date($format, $i);
        $end = strtotime($end);
        $dates = array($formatted_i);
        while($i < $end) {
            $month = $formatted_day = mktime(0, 0, 0, date('m', $i)+1, date('d',$i), date('Y', $i));
            $formatted_month = date($format, $month);
            $dates[] = $formatted_month;
            $i = $month;
        }
        return $dates;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $bet_amount = isset($row['bet_amount']) ? abs($row['bet_amount']) : 0;

        $result_amount = $row['result_amount'];


        /* if(!$this->enable_merging_rows){
            if($row['transaction_type'] == 'credit'){
                $win_amount = 0;
                $debit_amount = $row['amount'];
                $result_amount = abs($win_amount - $debit_amount);
            }
        } */

        $data = [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null,
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $result_amount,
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
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
                'bet_type'              => null
            ],
            'bet_details' => $this->rebuildBetDetailsFormat($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('EBET_SEAMLESS ', $data);
        return $data;

    }

    public function rebuildBetDetailsFormat($row, $extra = []) {
        $bet_details = [];

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['game_english_name'])) {
            $bet_details['game_name'] = $row['game_english_name'];
        }
        
        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (isset($row['extra'])) {
            $bet_details['extra'] = $row['extra'];
        }

        return $bet_details;
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
        switch($row['game_status']) {
            case 'ok':
            case 'settled':
            case Game_logs::STATUS_SETTLED:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 'refunded':
            case Game_logs::STATUS_REFUND:
                $row['note'] = 'Refund';
                $row['status'] = Game_logs::STATUS_REFUND;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }

        // settle unsettled bet status
        if (!$this->enable_merging_rows) {
            if ($row['transaction_type'] == self::TRANSACTION_DEBIT ) {
                $this->CI->load->model(['original_seamless_wallet_transactions']);
                $table_name = $this->getTransactionsTable();
                
                $selectedColumns = [];
                $order_by = ['field_name' => 'id', 'is_desc' => true];

                $credit_transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($table_name, [
                    // 'transaction_type' => self::TRANSACTION_CREDIT,
                    'player_id' => $row['player_id'],
                    'ref_transfer_id' => $row['transaction_id'],
                ], $selectedColumns, $order_by);

                if ($row['status'] != Game_logs::STATUS_SETTLED) {
                    if (isset($credit_transaction['game_status']) && ($credit_transaction['game_status'] == Game_logs::STATUS_SETTLED || $credit_transaction['game_status'] == Game_logs::STATUS_REFUND)) {
                        $row['status'] = $credit_transaction['game_status'];
                    }
                }

                if (empty($row['round_id'])) {
                    $row['round_id'] = $credit_transaction['historyId'];
                }
            }

            if ($row['transaction_type'] == self::TRANSACTION_CREDIT) {
                if ($row['game_status'] == Game_logs::STATUS_SETTLED) {
                    $row['bet_amount'] = 0;
                    $row['result_amount'] = $row['win_amount'];
                }
            }
        }

        $table_name = $this->jumb_game_logs;
        $query_bet_details = $this->queryBetDetails($table_name, $row['external_uniqueid']);

        $row['extra'] = [
            'jackpot_wins' => [
                isset($query_bet_details['jackpot']) ? abs($query_bet_details['jackpot']) : 0,
            ],
            'progressive_contributions' => [
                isset($query_bet_details['jackpotContribute']) ? abs($query_bet_details['jackpotContribute']) : 0,
            ]
        ];
    }

    private function queryBetDetails($table_name, $external_uniqueid){
        $this->CI->load->model('original_game_logs_model');

        $sqlRound="original.external_uniqueid = ?";

        $sql = <<<EOD
SELECT
jackpot,
jackpotContribute
FROM {$table_name} as original
WHERE
{$sqlRound}
EOD;
        $params=[
            $external_uniqueid,
        ];

        $this->CI->utils->debug_log('queryBetDetails sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }


    public function timestamps_milliseconds() {
        $timestampMs = round(microtime(true) * 1000);
        // $date = new DateTimeImmutable();
        // $timestampMs = (int) ($date->getTimestamp() . $date->format('v'));
        return $timestampMs;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $tableName = $this->getTransactionsTable();
        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$tableName} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
         }

        public function processTransactions(&$transactions){
            $temp_game_records = [];

            if(!empty($transactions)){
                foreach($transactions as $transaction){

                    $temp_game_record = [];
                    $temp_game_record['player_id'] = $transaction['player_id'];
                    $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                    $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                    $temp_game_record['amount'] = abs($transaction['amount']);
                    $temp_game_record['before_balance'] = $transaction['before_balance'];
                    $temp_game_record['after_balance'] = $transaction['after_balance'];
                    $temp_game_record['round_no'] = $transaction['round_no'];
                    $extra_info = @json_decode($transaction['extra_info'], true);
                    $extra=[];
                    $extra['trans_type'] = $transaction['trans_type'];
                    $extra['extra'] = $extra_info;
                    $temp_game_record['extra_info'] = json_encode($extra);
                    $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                    if($transaction['amount'] < 0){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }else{
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    }

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;

            $this->CI->utils->debug_log('TALLYNN: (' . __FUNCTION__ . ')', 'transactions:', $transactions);
        }

        public function decryptParams($encryptData){
            $key = $this->key;
            $iv = $this->iv;
            $decryptData = $this->decrypt($encryptData, $key, $iv);
            echo $decryptData . "";exit();
        }

        public function encryptParams($params){
            $data = $params;
            $key = $this->key;
            $iv = $this->iv;
            $encryptData = $this->encrypt($data, $key, $iv);
            return $encryptData;
        }

        
        public function defaultBetDetailsFormat($row) {
            $bet_details = [];

            if (isset($row['game_english_name'])) {
                $bet_details['game_name'] = $row['game_english_name'];
            }
            if (isset($row['round_id'])) {
                $bet_details['round_id'] = $row['round_id'];
            }
            if (isset($row['external_uniqueid'])) {
                $bet_details['bet_id'] = $row['external_uniqueid'];
            }

            if (isset($row['bet_amount'])) {
                $bet_details['bet_amount'] = $row['bet_amount'];
            }
            if (isset($row['result_amount'])) {
                $bet_details['bet_result'] = $row['result_amount'];
            }

            if (isset($row['start_at'])) {
                $bet_details['betting_datetime'] = $row['start_at'];
            }

            return $bet_details;
        }

        #OGP-34427
        public function getProviderAvailableLanguage() {
            return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
        }
}
