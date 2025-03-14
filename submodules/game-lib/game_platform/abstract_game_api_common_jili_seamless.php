<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Jili Gaming 
* Wallet Type: Seamless
* Asian Brand: 
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @bermar.php.ph

    Related File
    -routes.php
    -jili_seamless_service_api.php
**/

abstract class Abstract_game_api_common_jili_seamless extends Abstract_game_api {
    // free spin API
    public $free_spin_reference_id_prefix;
    public $free_spin_reference_id_length;
    public $free_spin_default_number_of_rounds;
    public $free_spin_default_game_ids;
    public $free_spin_default_bet_value;
    public $free_spin_default_validity_hours;
    public $timezone;
    public $launcher_mode;
    const POST = 'POST';
    const GET = 'GET';
        
    const MD5_FIELDS_FOR_ORIGINAL = [];
    const MD5_FLOAT_AMOUNT_FIELDS = [];
    const MD5_FIELDS_FOR_MERGE=['bet_amount','winlose_amount','status','updated_at'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','winlose_amount','after_balance'];

    const API_SUCCESS_CODE = 0;

    const IGNORE_KEYS=['HomeUrl','isJPEnabled', 'StartTime', 'BetValue'];
    const IGNORE_KEYS_SYNC_GAME_RECORDS=['GameId','FilterAgent'];

    const METHOD_AUTH = 'auth';
    const METHOD_BET = 'bet';
    const METHOD_CANCEL_BET = 'cancelBet';
    const METHOD_SESSION_BET = 'sessionBet';
	const METHOD_CANCEL_SESSION_BET = 'cancelSessionBet';

	const METHOD_SESSION_BETTYPE_BET = 1;
	const METHOD_SESSION_BETTYPE_PAYOUT = 2;

    const PAGE_LIMIT = 1000;

    const API_syncFishingGameRecords = 'syncFishingGameRecords';

    const DEMO_MODE = ['demo', 'fun', 'trial'];

    const FREE_SPIN_METHODS = [
        'createFreeRound',
        'cancelFreeRound',
        'queryFreeRound',
    ];

    const RESPONSE_CODE_FAILED = 'FAILED';

    public function __construct() {
        parent::__construct();

        $this->CI->load->model(array('original_game_logs_model'));

        $this->url              = $this->getSystemInfo('url');        
        $this->game_list_url    = $this->getSystemInfo('game_list_url', '');        
        $this->game_launch_url  = $this->getSystemInfo('game_launch_url', $this->url);
        $this->demo_url         = $this->getSystemInfo('demo_url', 'http://jiligames.com/plusplayer/PlusTrial/');
        $this->agent_id        = $this->getSystemInfo('key');
        $this->agent_key         = $this->getSystemInfo('secret');
        $this->language         = $this->getSystemInfo('language');
        $this->force_lang         = $this->getSystemInfo('force_lang', false);
        $this->home_link        = $this->getSystemInfo('home_link', '');        
        $this->jp_enabled        = $this->getSystemInfo('jp_enabled', 0);         
        $this->currency        = $this->getSystemInfo('currency');  

        //FOR TESTING
        $this->trigger_bet_error_response = $this->getSystemInfo('trigger_bet_error_response', 0);
        $this->trigger_cancelbet_error_response = $this->getSystemInfo('trigger_cancelbet_error_response', 0);                
        $this->trigger_sessionbet_error_response = $this->getSystemInfo('trigger_sessionbet_error_response', 0);                
        $this->trigger_cancelsessionbet_error_response = $this->getSystemInfo('trigger_cancelsessionbet_error_response', 0);                
        $this->trigger_auth_error_response = $this->getSystemInfo('trigger_auth_error_response', 0);
        $this->record_cancel_bet_transaction = $this->getSystemInfo('record_cancel_bet_transaction', true);

        $this->use_transaction = $this->getSystemInfo('use_transaction', true);
        $this->page_limit = $this->getSystemInfo('page_limit', self::PAGE_LIMIT);
        $this->timezone = $this->getSystemInfo('timezone', 'America/Puerto_Rico'); // America/Puerto_Rico | Canada/Atlantic

        // free spin API
        $this->free_spin_reference_id_prefix = $this->getSystemInfo('free_spin_reference_id_prefix', 'FS');
        $this->free_spin_reference_id_length = $this->getSystemInfo('free_spin_reference_id_length', 12);
        $this->free_spin_default_number_of_rounds = $this->getSystemInfo('free_spin_default_number_of_rounds', 1);
        $this->free_spin_default_game_ids = $this->getSystemInfo('free_spin_default_game_ids', '');
        $this->free_spin_default_bet_value = $this->getSystemInfo('free_spin_default_bet_value', '');
        $this->free_spin_default_validity_hours = $this->getSystemInfo('free_spin_default_validity_hours', '+2 hours');

        $this->enable_mock_cancel_bet = $this->getSystemInfo('enable_mock_cancel_bet', false);
        $this->enable_mock_cancel_player_list = $this->getSystemInfo('enable_mock_cancel_player_list', []);

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        // default launcher_mode 
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'singleOnly');
        
        $this->URI_MAP = array(
            self::API_queryForwardGame => '/singleWallet/LoginWithoutRedirect', 
            self::API_createFreeRoundBonus => '/CreateFreeSpin',
            self::API_cancelFreeRoundBonus => '/CancelFreeSpin',
            self::API_queryFreeRoundBonus => '/GetFreeSpinRecordByReferenceID',
            self::API_queryGameListFromGameProvider => '/GetGameList',
            //self::API_syncGameRecords => '/GetBetRecordByTime', 
            //self::API_syncFishingGameRecords => '/GetFishBetRecordByTime', 
            
        );
    
        $this->METHOD_MAP = array(
            self::API_queryForwardGame => self::GET,  
            self::API_createFreeRoundBonus => self::POST,
            self::API_cancelFreeRoundBonus => self::POST,
            self::API_queryFreeRoundBonus => self::POST,
            self::API_queryGameListFromGameProvider => self::GET,
            //self::API_syncGameRecords => self::POST,    
            //self::API_syncFishingGameRecords => self::POST,    
        );
        
    }

    public function generateKeyG($dateObj = null){
        //$timezone = $this->getSystemInfo('timezone', 'Canada/Atlantic');
        if(empty($dateObj)){            
            $dateObj = new DateTime("now", new DateTimeZone($this->timezone) );
        }
        $now = $dateObj->format('ymj');
        
        //$now='211118';
        //211119
        $keyGString = $now . $this->agent_id . $this->agent_key;
        $keyG = md5($keyGString);
        $this->CI->utils->debug_log('JILI (generateKeyG)', 
        'agent_key', $this->agent_key, 
        'agent_id', $this->agent_id, 
        'now', $now,
        'keyGString', $keyGString,
        'keyG', $keyG);		
        return $keyG;        
    }

    public function generateKey($params, $ignore){
        $keyG = $this->generateKeyG();

        foreach($params as $key => $value){
            if(in_array($key, $ignore)){
                unset($params[$key]);
            }
        }

        $querystring = urldecode(http_build_query($params));



        $key = md5($querystring . $keyG);        
        $random1 = $this->getSystemInfo('random1', '000000');
        $random2 = $this->getSystemInfo('random2', '000000');
        $hash = $random1 . $key . $random2;

        $this->CI->utils->debug_log('JILI (generateKey)', 
        'querystring', $querystring, 
        'key', $key, 
        'hash', $hash);		
        return $hash;     
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
        $this->method = $this->getMethodName($apiName);

        $this->CI->utils->debug_log('JILI (generateUrl)', $apiName, $params, $this->method);		

        $apiUri = $this->URI_MAP[$apiName];                
		$url = $this->url . $apiUri;

        if($apiName == self::API_queryGameListFromGameProvider){
            $url = $this->game_list_url . $apiUri;
        }

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
        }
        
		$this->CI->utils->debug_log('JILI (generateUrl)', $apiName, $params, $this->method, $url);		

		return $url;
	}

	protected function getHttpHeaders($params){
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
		$headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',				
        ];
        
		return $headers;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        $this->utils->debug_log("JILI SEAMLESS: (createPlayer)");

        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for jili seamless api";
        if($return){
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for jili seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("JILI SEAMLESS: (depositToGame)");

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
        $this->utils->debug_log("JILI SEAMLESS: (withdrawFromGame)");

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
		$this->CI->utils->debug_log('JILI (processResultBoolean)');	
        
        $success = false;

        if(isset($resultArr['ErrorCode']) && self::API_SUCCESS_CODE==$resultArr['ErrorCode']){
            $success = true;
        }		

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('JILI got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
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
        $this->utils->debug_log("JILI SEAMLESS: (queryForwardGame)", $playerName, $extra);   
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($this->language);
        }
			
        if (isset($extra['home_link'])) {
            $this->home_link = $extra['home_link'];
        }  

        $mode = isset($extra['game_mode'])?$extra['game_mode']:null;
		
		if(in_array($mode, self::DEMO_MODE)){
            $url = $this->demo_url . $extra['game_code'];
            $url .= '/'.$language;
			return ['success'=>true, 'url'=>$url];
		}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $token = $this->getPlayerTokenByUsername($playerName);
        $params = array(                                
            "Token" => $token,
            "GameId" => $extra['game_code'],
            "Lang" => $language,
            "AgentId" => $this->agent_id,
        );

        $params['Key'] = $this->generateKey($params, self::IGNORE_KEYS);
        $params['HomeUrl'] = $this->home_link;
        $params['isJPEnabled'] = $this->jp_enabled;

        $this->method = self::GET;
        $this->utils->debug_log("JILI SEAMLESS: PARAMS", $params);  
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

	public function processResultForQueryForwardGame($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId, 'url' => null];
        $this->utils->debug_log("JILI SEAMLESS: (processResultForQueryForwardGame)", 'resultArr', $resultArr);

        if($success){
            if(isset($resultArr['Data']) && isset($resultArr['Data'])){
                $result['url']=$resultArr['Data'];
            }else{
                $success=false;
            }
        }

        return [$success, $result];
	}

    public function queryGameListFromGameProvider($extra = null){
        $this->utils->debug_log("JILI SEAMLESS: (queryGameListFromGameProvider) extra", $extra);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider'
        );

        $params = array(                                
            "AgentId" => $this->agent_id,
        );
        $params['AgentId'] = $this->agent_id;
        $params['Key'] = $this->generateKey($params, self::IGNORE_KEYS);

        $this->method = self::GET;

        $this->utils->debug_log("JILI SEAMLESS: queryGameListFromGameProvider PARAMS", $params);  
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId, 'url' => null];
        $this->utils->debug_log("JILI SEAMLESS: (processResultForQueryGameListFromGameProvider)", 'resultArr', $resultArr);

        if($success){
            if(isset($resultArr['Data']) && isset($resultArr['Data'])){
                $result['games']=$resultArr['Data'];
            }else{
                $success=false;
            }
        }

        return [$success, $result];
    }
    public function customHttpCall($ch, $params){
        $this->CI->utils->debug_log('JILI (customHttpCall)', $this->method);	
		switch ($this->method){
            case self::POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);				
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				break;
		}
		$this->utils->debug_log('JILI (customHttpCall) ', $this->method, http_build_query($params));
    }

	public function getLauncherLanguage($language){
        if($this->force_lang && $this->language){
            return $this->language;
        }
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en-US'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id-ID'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vi-VN'; // vietnamese
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th-TH'; // thai
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-br':
                $lang = 'pt-BR'; // portuguese
                break;
            case Language_function::INT_LANG_JAPANESE:
            case 'ja':
            case 'ja-jp':
                $lang = 'ja-JP'; // Japanese
                break;
            case 'pt-pt':
                $lang = 'pt-PT';
                break;
            default:
                $lang = 'en-US'; // default as english
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
        $this->utils->debug_log("JILI SEAMLESS: (queryPlayerBalance)");
        
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
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function calculateAmount($data, $realBet = true){
        $bet_amount = isset($data['bet_amount'])?abs($data['bet_amount']):0;
        $winlose_amount = isset($data['winlose_amount'])?abs($data['winlose_amount']):0;
        //$jp_contribute = isset($data['jp_contribute'])?abs($data['jp_contribute']):0;
        $jp_win = isset($data['jp_win'])?abs($data['jp_win']):0;
		$preserve = isset($data['preserve'])?abs($data['preserve']):0;
		$trans_type = isset($data['trans_type'])?$data['trans_type']:false;
		$session_type = isset($data['type'])?$data['type']:false;

		$balanceAdjustment = 0;
		$total_debit = $bet_amount;
		$total_credit = $winlose_amount + $jp_win;

        if(!$realBet){
            if($trans_type && $trans_type==self::METHOD_SESSION_BET){
                if($session_type==self::METHOD_SESSION_BETTYPE_BET){
                    $total_debit += $preserve;
                }elseif($session_type==self::METHOD_SESSION_BETTYPE_PAYOUT){
                    $total_credit += $preserve;
                }
            }
        }
	
		$balanceAdjustment = $total_credit-$total_debit;

		return $balanceAdjustment;     
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='t.updated_at >= ? and t.updated_at <= ?';
$sql = <<<EOD
SELECT 
t.id as sync_index,
t.external_uniqueid,
t.response_result_id,

t.wagers_time_parsed as start_at,
t.wagers_time_parsed as end_at,
t.wagers_time_parsed as bet_at,
t.updated_at as updated_at,
t.player_id as player_id,
t.user_id as player_username,
t.game as game,
t.game as game_code_original,
t.round as round,
t.user_id as username,
t.trans_type as trans_type,
t.type,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.balance_adjustment_amount as balance_adjustment_amount,

t.bet_amount,
t.winlose_amount,
t.jp_win,
t.preserve,
t.turnover,
t.type session_bet_type,
t.session_id session_id,

t.trans_status status,

null as md5_sum,
gd.game_code as game_code,
gd.game_name as game_name,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id,
gd.english_name as real_game_name

FROM {$this->original_transactions_table} as t
LEFT JOIN game_description as gd ON t.game = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE

{$sqlTime} AND (t.trans_type = 'bet' OR t.trans_type = 'sessionBet') and 
t.trans_status>0 AND is_failed=0;

EOD;

        $params=[$this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        $result = $this->preProcessTransactions($result);

        return $result;
    }

    public function preProcessTransactions(&$data){
        //exclude in merging transactions that is not real bet
        $temp = [];
        foreach($data as $key => $row){
            if(
                $row['trans_type']==self::METHOD_SESSION_BET && 
                $row['session_bet_type']==1 &&
                $row['bet_amount'] <=0 &&
                $row['preserve'] >0
            ){
                if(!$this->enable_merging_rows){
                    $temp[] = $row;
                }else{
                    unset($data[$key]);
                }
            }else{
                $temp[] = $row;
            }
        }
        return $temp;
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        switch ($row['status']) {
            case Game_logs::STATUS_SETTLED:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case Game_logs::STATUS_CANCELLED:
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            case Game_logs::STATUS_REFUND:
                $row['status'] = Game_logs::STATUS_REFUND;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }
    }
    
    private function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game'];
        $external_game_id = $row['game_code_original'];        
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);

	}

    public function generateBetDetails($row){
        $betDetails = [];
        if(isset($row['trans_type']) && !empty($row['trans_type'])){
            $betDetails['transaction_type'] = $row['trans_type'];
        }
        if(isset($row['round']) && !empty($row['round'])){
            $betDetails['round'] = $row['round'];
        }
        if(isset($row['session_id']) && !empty($row['session_id'])){
            $betDetails['session_id'] = $row['session_id'];
        }
        if(isset($row['session_bet_type']) && !empty($row['session_bet_type'])){
            $betDetails['session_bet_type'] = null;
            if($row['session_bet_type']==1){
                $betDetails['session_bet_type'] = 'bet';
            }elseif($row['session_bet_type']==2){
                $betDetails['session_bet_type'] = 'settle';
            }
        }
        if(isset($row['preserve']) && !empty($row['preserve'])){
            $betDetails['preserve'] = $row['preserve'];
        }
        if(isset($row['turnover']) && !empty($row['turnover'])){
            $betDetails['turnover'] = $row['turnover'];
        }
        if(isset($row['jp_win']) && !empty($row['jp_win'])){
            $betDetails['jp_win'] = $row['jp_win'];
        }
        if(isset($row['jp_contribute']) && !empty($row['jp_contribute'])){
            $betDetails['jp_contribute'] = $row['jp_contribute'];
        }

        return $betDetails;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->utils->debug_log("JILI SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow)");        

        $result_amount = $this->calculateAmount($row);		
		$bet_amount = $row['bet_amount'];



        if(!$this->enable_merging_rows && $row['trans_type'] == self::METHOD_SESSION_BET){
            if($row['type'] ==self::METHOD_SESSION_BETTYPE_BET){
                $bet_amount = $row['balance_adjustment_amount'];
                $result_amount = -1 * $row['balance_adjustment_amount'];
            }else{
                //METHOD_SESSION_BETTYPE_PAYOUT
                $bet_amount = 0;
                $result_amount = $row['balance_adjustment_amount'];
            }
        }

        if(empty($row['md5_sum'])){            
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
	

        $round = $row['round'];
        if($row['trans_type']==self::METHOD_SESSION_BET||$row['trans_type']==self::METHOD_CANCEL_SESSION_BET){
            $round = $row['session_id'];
        }
        
        $row['transaction_id'] = $row['external_uniqueid'];
        $row['round_id'] = $round;
        $row['win_amount'] = $row['winlose_amount'];

        return [           
            'game_info'=>[
                'game_type_id' => $row['game_type_id'], 
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'], 
                'game_type' => null, 
                'game' => $row['game']
            ],
            'player_info'=>[
                'player_id' => $row['player_id'], 
                'player_username' => $row['player_username']
            ],
            'amount_info'=>[
                'bet_amount' => $this->gameAmountToDB($bet_amount), 
                'result_amount' => $this->gameAmountToDB($result_amount),
                'bet_for_cashback' => $this->gameAmountToDB($bet_amount), 
                'real_betting_amount' => $this->gameAmountToDB($bet_amount),
                'win_amount' => null, 
                'loss_amount' => null, 
                'after_balance' => $row['after_balance'],
                'before_balance' => $row['before_balance']
            ],
            'date_info'=>[
                'start_at' => $this->gameTimeToServerTime($row['start_at']), 
                'end_at' => $this->gameTimeToServerTime($row['end_at']), 
                'bet_at' => $this->gameTimeToServerTime($row['bet_at']),
                'updated_at' => $row['updated_at']
            ],
            'flag'=>Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0, 
                'external_uniqueid' => $row['external_uniqueid'], 
                'round_number' => $round,
                'md5_sum' => $row['md5_sum'], 
                'response_result_id' => $row['response_result_id'], 
                'sync_index' => $row['sync_index'],
                'bet_type' => null 
            ],
            'bet_details' => $this->preprocessBetDetails($row),
            //'bet_details' => $this->generateBetDetails($row),
            'extra' => [],            
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function queryTransactionByDateTime($startDate, $endDate){

$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
t.bet_amount as bet_amount,
t.winlose_amount as winlose_amount,
t.jp_win as jp_win,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type trans_type,
t.balance_adjustment_method balance_adjustment_method,
t.balance_adjustment_amount balance_adjustment_amount
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?  and 
t.trans_status<>-1  and 
t.trans_status<>-2 and is_failed=0
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
                $temp_game_record['amount'] = $transaction['balance_adjustment_amount'];
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['balance_adjustment_method'] == 'credit' ? 'win' : $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['balance_adjustment_method']=='debit'){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("JILI SEAMLESS: (queryPlayerBalanceByPlayerId)");

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function getGameTimeToServerTime() {
        return $this->getSystemInfo('gameTimeToServerTime','+12 hours');
    }

    public function getServerTimeToGameTime() {
        return $this->getSystemInfo('serverTimeToGameTime','-12 hours');
    }

    public function apiGameTime($dateTime = 'now', $format = 'Y-m-d H:i:s', $modify = '+0 hours') {
        $dateTime = new DateTime($dateTime, new DateTimeZone($this->timezone));
        $dateTime->modify($modify);
        return $dateTime->format($format);
    }

    public function createFreeRound($playerName = null, $extra = []) {
        $gameUsername = !empty($extra['Account']) ? $extra['Account'] : $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $currency = !empty($extra['currency']) ? $extra['currency'] : $this->currency;
        $referenceId = !empty($extra['ReferenceId']) ? $extra['ReferenceId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_spin_reference_id_prefix, $this->free_spin_reference_id_length);
        $freeSpinValidity = !empty($extra['FreeSpinValidity']) ? $extra['FreeSpinValidity'] : $this->apiGameTime('now', 'Y-m-d\TH:i:s', $this->free_spin_default_validity_hours);
        $numberOfRounds = !empty($extra['NumberOfRounds']) ? $extra['NumberOfRounds'] : $this->free_spin_default_number_of_rounds;
        $gameIds = !empty($extra['GameIds']) ? $extra['GameIds'] : $this->free_spin_default_game_ids;
        $betValue = isset($extra['BetValue']) ? $extra['BetValue'] : $this->free_spin_default_bet_value;
        $startTime = !empty($extra['StartTime']) ? $extra['StartTime'] : $this->apiGameTime('now', 'Y-m-d\TH:i:s');

        $params = [
            'Account' => $gameUsername,
            'Currency' => $currency,
            'ReferenceId' => $referenceId,
            'FreeSpinValidity' => $freeSpinValidity,
            'NumberOfRounds' => $numberOfRounds,
            'GameIds' => $gameIds,
            'StartTime' => $startTime,
            'AgentId' => $this->agent_id,
        ];

        if ($betValue != '') {
            $params['BetValue'] = $betValue;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'game_username' => $gameUsername,
            'player_id' => $playerId,
            'free_rounds' => $numberOfRounds,
            'transaction_id' => $referenceId,
            'currency' => $currency,
            'expired_at' => $freeSpinValidity,
            'extra' => $extra,
            'request' => $params,
        ];

        $params['Key'] = $this->generateKey($params, self::IGNORE_KEYS);

        return $this->callApi(self::API_createFreeRoundBonus, $params, $context);
    }

    public function processResultForCreateFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $free_rounds = $this->getVariableFromContext($params, 'free_rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');
        $request = $this->getVariableFromContext($params, 'request');

        if ($success) {
            $result = [
                'transaction_id' => $transaction_id,
                'expiration_date' => $expired_at,
            ];

            $data = [
                'player_id' => $player_id,
                'game_platform_id' => $this->getPlatformCode(),
                'free_rounds' => $free_rounds,
                'transaction_id' => $transaction_id,
                'currency' => $currency,
                'expired_at' => $expired_at,
                'extra' => json_encode($extra),
                'raw_data' => json_encode($request),
            ];

            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->insertTransaction($data);
        } else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function cancelFreeRound($transaction_id = null, $extra = []) {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'transaction_id' => $transaction_id,
        ];

        if (!empty($extra['ReferenceId'])) {
            $transaction_id = $extra['ReferenceId'];
        }

        $params = [
            'ReferenceId' => $transaction_id,
            'AgentId' => $this->agent_id,
        ];

        $params['Key'] = $this->generateKey($params, self::IGNORE_KEYS);

        return $this->callApi(self::API_cancelFreeRoundBonus, $params, $context);
    }

    public function processResultForCancelFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');

        $result = [
            'message' => '',
        ];

        if ($success) {
            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->cancelTransaction($transaction_id, $this->getPlatformCode());

            if (!empty($transaction_id)) {
                $result['transaction_id'] = $transaction_id;
            }

            $result['message'] = 'Cancelled successfully';
        } else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function queryFreeRound($playerName = null, $extra = []) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $referenceId = isset($extra['ReferenceID']) ? $extra['ReferenceID'] : null;
        
        // $this->CI->load->model(['free_round_bonus_model']);
        // $playerId = $this->CI->free_round_bonus_model->getSpecificColumn('free_round_bonuses', 'player_id', ['transaction_id' => $referenceId]);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
            'game_username' => $gameUsername,
            // 'playerId' => $playerId,
        ];

        $params = [
            'ReferenceID' => $referenceId,
            'AgentId' => $this->agent_id,
        ];

        $params['Key'] = $this->generateKey($params, self::IGNORE_KEYS);

        return $this->callApi(self::API_queryFreeRoundBonus, $params, $context);
    }

    public function processResultForQueryFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($success) {
            $result = [
                'free_round_list' => !empty($resultArr['Data']) ? $resultArr['Data'] : [],
            ];
        }
        else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function callback($request, $method) {
        if (!in_array($method, self::FREE_SPIN_METHODS)) {
            return [
                'returnCode' => self::RESPONSE_CODE_FAILED,
                'message' => 'Invalid method',
            ];
        }

        return $this->$method('', $request);
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

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['real_game_name'])) {
            $bet_details['game_name'] = $row['real_game_name'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function batchRefund($data = [], $extra = [])
    {
        $baseUrl = $this->getSystemInfo("batch_refund_api_url", "http://admin.og.local");

        $game_platform_id = $this->getPlatformCode();

        $table = $this->getTransactionsTable();
        // Fetch all records in a single query
        if(empty($data)) return false;

        $sql = "SELECT * FROM $table WHERE external_uniqueid IN (" . implode(',', array_fill(0, count($data), '?')) . ") AND game_platform_id = ?";
        $params = array_merge($data, [$this->getPlatformCode()]);
        $query = $this->CI->db->query($sql, $params);

        if ($query) {
            foreach ($query->result() as $result) {
                if (empty($result)) {
                    continue;
                }

                $external_uniqueid = isset($result->external_uniqueid) ? $result->external_uniqueid : null;
                $token = isset($result->token) ? $result->token : null;
                $currency = isset($result->currency) ? $result->currency : null;
                $game = isset($result->game) ? $result->game : null;
                $round = isset($result->round) ? $result->round : null;
                $token = isset($result->token) ? $result->token : null;
                $bet_amount = isset($result->bet_amount) ? $result->bet_amount : null;
                $winlose_amount = isset($result->winlose_amount) ? $result->winlose_amount : null;
                $player_id = isset($result->player_id) ? $result->player_id : null;
                $game_username = $this->getGameUsernameByPlayerId($player_id);

                $params = array(
                    "game"             => $game,
                    "reqId"            => 'cancel-' . $external_uniqueid,
                    "round"            => $round,
                    "token"            => $token,
                    "userId"           => $game_username,
                    "currency"         => $currency,
                    "betAmount"        => $bet_amount,
                    "winloseAmount"    => $winlose_amount
                );

                $this->method = self::POST;
                $api_url = $baseUrl . site_url("jili_seamless_service_api/$game_platform_id/cancelBet"); 

                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

                $api_response = curl_exec($ch);

                $unique_id = $params['reqId'];
                $this->CI->utils->debug_log("response: $unique_id", $api_response);
                curl_close($ch);
            }
        } else {
            return false;
        }
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $pendingStatus = Game_logs::STATUS_PENDING;
        $transTypeBet = self::METHOD_BET;
        $transTypeSessionBet = self::METHOD_SESSION_BET;

        $sql = <<<EOD
SELECT 
original.round as round_id, original.reg_id as transaction_id, game_platform_id
from {$this->original_transactions_table} as original
where
original.trans_status=?
and (original.trans_type=? or original.trans_type=?)
and {$sqlTime}
EOD;


        $params=[
            $pendingStatus,
            $transTypeBet,
            $transTypeSessionBet,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('JILi SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $this->original_transactions_table = $this->getTransactionsTable();

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.trans_type as transaction_type,
original.trans_status as status,
original.game_platform_id,
original.player_id,
original.round as round_id,
original.reg_id as transaction_id,
ABS(SUM(original.bet_amount)) as amount,
ABS(SUM(original.bet_amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_uniqueid
from {$this->original_transactions_table} as original
left JOIN game_description as gd ON original.game = gd.external_game_id and gd.game_platform_id=?
where
round=? and reg_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('JILI SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
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
trans_status
FROM {$this->original_transactions_table}
WHERE
game_platform_id=? AND external_uniqueid=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$trans['trans_status']);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }
}//end of class
