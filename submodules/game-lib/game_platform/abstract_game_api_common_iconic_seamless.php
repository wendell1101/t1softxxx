<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Iconic Gaming 
* Wallet Type: Seamless
* Asian Brand: 
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @bermar.php.ph

    Related File
    -routes.php
    -iconic_service_api.php
**/

abstract class Abstract_game_api_common_iconic_seamless extends Abstract_game_api {

    public $use_game_link_method;
    public $default_game_type;
    public $show_hash_code;

    const POST = 'POST';
    const GET = 'GET';
    
    //const MD5_FIELDS_FOR_ORIGINAL = ['bet_amount','real_bet_amount','result_amount','balance_after','balance_before'];
    //const MD5_FLOAT_AMOUNT_FIELDS = ['bet_amount','real_bet_amount','result_amount','balance_after','balance_before'];
    const MD5_FIELDS_FOR_ORIGINAL = ['bet', 'valid_bet','win','status'];
    const MD5_FLOAT_AMOUNT_FIELDS = ['bet','win', 'valid_bet'];
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','status','orig_updated_at'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];

    public function __construct() {
        parent::__construct();

        $this->CI->load->model(array('original_game_logs_model'));

        $this->url              = $this->getSystemInfo('url');
        $this->game_launch_url  = $this->getSystemInfo('game_launch_url', 'https://launcher-stage.iconic-gaming.com/play');
        $this->language         = $this->getSystemInfo('language');
        $this->platform_id         = $this->getSystemInfo('platform_id', 1);
        $this->secure_code         = $this->getSystemInfo('secure_code');
        $this->game_conversion_rate         = $this->getSystemInfo('game_conversion_rate', 100);

        $this->jwt_username         = $this->getSystemInfo('jwt_username', '');
        $this->jwt_password         = $this->getSystemInfo('jwt_password', '');
        $this->jwt_duration         = $this->getSystemInfo('jwt_duration', 43800);//1 month
        $this->force_generate_token = $this->getSystemInfo('force_generate_token', false);

        $this->sync_page_size         = $this->getSystemInfo('sync_page_size', 100);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);
        $this->max_page = $this->getSystemInfo('max_page', 20);     
        $this->delay_service_response = $this->getSystemInfo('delay_service_response', 20);//this is for testing retry            
        $this->home_link = $this->getSystemInfo('home_link', '');        
        
        $this->is_auth = false;

        $this->continue_loop = false;

        $this->use_new_token = $this->getSystemInfo('use_new_token', false);        
        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');        
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');        
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');

        $this->use_game_link_method = $this->getSystemInfo('use_game_link_method', true);
        $this->default_game_type = $this->getSystemInfo('default_game_type', 'all');
        $this->show_hash_code = $this->getSystemInfo('show_hash_code', false);
        

        $this->URI_MAP = array(
            self::API_generateToken => '/service/login',        
            self::API_syncGameRecords => '/service/api/v1/profile/rounds',
            self::API_queryGameListFromGameProvider => '/service/api/v1/games',
        );
    
        $this->METHOD_MAP = array(
            self::API_generateToken => self::POST,        
            self::API_syncGameRecords => self::GET        
        );

        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['deposit','bet']);
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

        $this->CI->utils->debug_log('ICONIC (generateUrl)', $apiName, $params, $this->method);		

        $apiUri = $this->URI_MAP[$apiName];                
		$url = $this->url . $apiUri;

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
        }
        
		$this->CI->utils->debug_log('ICONIC (generateUrl)', $apiName, $params, $this->method, $url);		

		return $url;
	}

	/**
	 * will check timeout, if timeout then call again
	 * @return token
	 */
    public function getAvailableApiToken(){
        return $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
    }

    /**
     *
     * Login And Get JWT Token (登入並取得 JWT 令牌)
     *
     * @return      array
     *
     */
	public function generateToken(){
        $this->CI->utils->debug_log('ICONIC (generateToken)');
        
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGenerateToken',
			'playerId'=>null,
		);

		$params = [
            'username'=>$this->jwt_username,
            'password'=>$this->jwt_password,
        ];

        $this->is_auth = true;
		return $this->callApi(self::API_generateToken, $params, $context);
	}

	public function processResultForGenerateToken($params){
        	
		$this->CI->utils->debug_log('ICONIC (processResultForGenerateToken)', $params);	

		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

		if($success){
			$api_token = @$resultArr['token'];			
			$token_timeout = new DateTime($this->utils->getNowForMysql());
			$token_timeout->modify("+{$this->jwt_duration} minutes");
			$result['api_token']=$api_token;
			$result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
        }
        
		$this->CI->utils->debug_log('ICONIC (processResultForGenerateToken) result:', $result);	
		return array($success,$result);
	}

	protected function getHttpHeaders($params){
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

		$headers = [];
		if(!$this->is_auth){

            if($this->force_generate_token){
                $this->generateToken();
            }
            
            $clone = clone $this;
			$auth = $clone->getAvailableApiToken();
            
			$headers = [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer '.$auth,
            ];
            
        }
        
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
        $this->utils->debug_log("ICONIC SEAMLESS: (depositToGame)");

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
        $this->utils->debug_log("ICONIC SEAMLESS: (withdrawFromGame)");

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
		$this->CI->utils->debug_log('ICONIC (processResultBoolean)');	
        
        $success = false;

        if(!isset($resultArr['error'])){
            $success = true;
        }		

        if(isset($resultArr['error']['status']) && $resultArr['error']['status']==401){
            $this->generateToken();//force generate token
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ICONIC got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}
    
    public function buildUrl($url, $data){
        $params = $data;

        if ($this->use_game_link_method) {
            unset($params['lang']);
            unset($params['platform']);
            $url .= "&" . http_build_query($params);
        } else {
            $url .= "?" . http_build_query($params);
        }

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
        $this->utils->debug_log("ICONIC SEAMLESS: (queryForwardGame)");

        if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }

        switch ($extra['game_type']) {
            case 'slots':
                $game_type = 'slot';
                break;
            case 'fishing_game':
                $game_type = 'fish';
                break;
            case 'arcade':
                $game_type = 'coc';
                break;
            case 'card_games':
                $game_type = 'card';
                break;
            default:
                $game_type = $this->default_game_type;
                break;
        }

        if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($this->language);
        }

        if (isset($extra['home_link'])) {
            $this->home_link = $extra['home_link'];
        }  

        if(isset($extra['game_mode']) && ($extra['game_mode']=='fun' || $extra['game_mode']=='trial')){
            $data = array(
                "lang" => $language,
                "home_URL" => $this->home_link,
                "platform" => $this->platform_id
            );
        }else{
            $token = $this->getPlayerTokenByUsername($playerName);
            if($this->use_new_token){
                $token = urlencode('token:'.$this->generatePlayerToken($playerName));
            }
            $data = array(
                "token" => $token,
                "lang" => $language,
                "home_URL" => $this->home_link,
                "platform" => $this->platform_id
            );
        }

        if ($this->use_game_link_method) {
            $rebuild_extra = [
                'game_type' => $game_type,
                'game_code' => $extra['game_code'],
                'language' => $language,
            ];

            $game_link = $this->queryGameLink($rebuild_extra);

            if (!empty($game_link)) {
                $game_launch_url = $game_link;
            }else{
                $game_launch_url = $this->game_launch_url . '/' . $extra['game_code'];
            }
        } else {
            $game_launch_url = $this->game_launch_url . '/' . $extra['game_code'];
        }
        
        $result = array(
            "success" => true,
            "url" => $this->buildUrl($game_launch_url, $data)
        );

        $this->utils->debug_log("ICONIC SEAMLESS: (queryForwardGame)", $result);
        return $result;
    }

    public function queryGameLink($extra = null)
    {
        $this->CI->utils->debug_log('ICONIC (queryGameLink)');
        $href = null;
        $results = $this->queryGameListFromGameProvider($extra);

        if (!empty($results['games'])) {
            foreach ($results['games'] as $data) {
                $product_id = isset($data['productId']) ? $data['productId'] : null;
                
                if ($product_id == $extra['game_code']) {
                    $href = isset($data['href']) ? $data['href'] : null;
                    break;
                }
            }
        }

        $this->CI->utils->debug_log('ICONIC (queryGameLink) results:', $results);	
        return $href;
    }

    public function queryGameListFromGameProvider($extra = null)
    {
        $this->CI->utils->debug_log('ICONIC (queryGameListFromGameProvider)');

        $game_type = $extra['game_type'];
        $game_code = $extra['game_code'];
        $language = $extra['language'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
            'game_code' => $game_code,
        ];

        $params = [
            'type' => $game_type,
            'lang' => $language,
        ];

		return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params)
    {
        $this->CI->utils->debug_log('ICONIC (processResultForQueryGameListFromGameProvider)', $params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = [];

        if ($success) {
            $result['games'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        }

        $this->CI->utils->debug_log('ICONIC (processResultForQueryGameListFromGameProvider) resultArr:', $resultArr);	
        return array($success, $result);
    }

    public function generatePlayerToken($playerName){
        $token = $this->encrypt($playerName);
        return $token;
    }

    public function encrypt($data){
        if(is_array($data)){
            $data = json_encode($data);
        }
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_encrypt($data, $this->encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function decrypt($data){
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($data), $this->encrypt_method, $key, 0, $iv);
        return $output;
    }

    public function customHttpCall($ch, $params){
        $this->CI->utils->debug_log('ICONIC (customHttpCall)', $this->method);	
		switch ($this->method){
            case self::POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);				
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				break;
		}
		$this->utils->debug_log('ICONIC (customHttpCall) ', $this->method, http_build_query($params));
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
        $this->utils->debug_log("ICONIC SEAMLESS: (queryPlayerBalance)");
        
        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        return $result;
    }

    public function syncOriginalGameLogs($token = false) {
		$this->CI->utils->debug_log('ICONIC (syncOriginalGameLogs)', $token, $this->original_gamelogs_table);	

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");		
    	$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
        
        $currentPage = 1;
        $this->continue_loop = true;

    	while ($this->continue_loop) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
			);

			$params = array(
				'start' => $this->convertDateTimeToUnixTimestamp($queryDateTimeStart),
                'end' => $this->convertDateTimeToUnixTimestamp($queryDateTimeEnd),			
                'pageSize' => $this->sync_page_size,			
                'page' => $currentPage
            );
            
			$this->is_auth = false;
            $result[] = $this->callApi(self::API_syncGameRecords, $params, $context);
            
			sleep($this->sync_sleep_time);			

            $currentPage++;       
            
            if($currentPage>$this->max_page ){
                $this->continue_loop = false;
            }
		}

		return array("success" => true, "results"=>$result);
    }
    
    public function convertDateTimeToUnixTimestamp($dateTime){
        return strtotime($dateTime)*1000;
    }

    public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->utils->debug_log('ICONIC (processResultForSyncOriginalGameLogs)');

        $this->CI->load->model('original_game_logs_model');
		
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,null,true);
		$result = array('data_count'=>0);

        $gameRecords = !empty($resultArr['data'])?$resultArr['data']:[];
        
		if($success&&!empty($gameRecords)){
            $extra = ['response_result_id'=>$responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_unique_id',
                'external_unique_id',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
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
		}else{            
            $this->continue_loop = false;
        }

		return array($success, $result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra){

		//$this->CI->utils->debug_log('ICONIC (rebuildGameRecords)', $gameRecords);

		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$temp_new_gameRecords = [];
        	$recordId = $record['id'];
            $temp_new_gameRecords['orig_id'] = isset($record['id'])?$record['id']:null;
            $temp_new_gameRecords['orig_created_at'] = isset($record['createdAt'])?$record['createdAt']:null;
            $temp_new_gameRecords['orig_updated_at'] = isset($record['updatedAt'])?$record['updatedAt']:null;
            $temp_new_gameRecords['win'] = isset($record['win'])?$record['win']:null;
            $temp_new_gameRecords['bet'] = isset($record['bet'])?$record['bet']:null;
            $temp_new_gameRecords['status'] = isset($record['status'])?$record['status']:null;
            $temp_new_gameRecords['parent_id'] = isset($record['parentId'])?$record['parentId']:null;
            $temp_new_gameRecords['parent'] = isset($record['parent'])?$record['parent']:null;
            $temp_new_gameRecords['player_id'] = isset($record['playerId'])?$record['playerId']:null;
            $temp_new_gameRecords['player'] = isset($record['player'])?$record['player']:null;            
            $temp_new_gameRecords['game_id'] = isset($record['gameId'])?$record['gameId']:null;
            $temp_new_gameRecords['game'] = isset($record['game'])?$record['game']:null;
            $temp_new_gameRecords['game_type'] = isset($record['gameType'])?$record['gameType']:null;
            $temp_new_gameRecords['product_id'] = isset($record['productId'])?$record['productId']:null;
            $temp_new_gameRecords['currency'] = isset($record['currency'])?$record['currency']:null;
            $temp_new_gameRecords['valid_bet'] = isset($record['validBet'])?$record['validBet']:null;            
            $temp_new_gameRecords['external_unique_id'] = isset($record['id'])?$record['id']:null;
            $temp_new_gameRecords['response_result_id'] = $extra['response_result_id'];			
            
			$new_gameRecords[$recordId] = $temp_new_gameRecords;
        }

        $gameRecords = $new_gameRecords;
	}

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='original.updated_at >= ? and original.updated_at <= ?';
        $sql = <<<EOD
SELECT 
original.id as sync_index,
original.orig_id as orig_id,
original.orig_created_at,
original.orig_updated_at,
original.win,
original.win result_amount,
original.bet,
original.status,
original.parent_id,
original.parent,
original.player_id as orig_player_id,
original.player as orig_player,
original.game as orig_game_name,
original.game_type,
original.currency,
original.valid_bet as bet_amount,
original.response_result_id,
original.external_unique_id,
original.external_unique_id external_uniqueid,
original.updated_at,
original.md5_sum,
original.product_id as game_code,

gpa.login_name as player_username,
gpa.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id,
gd.game_name as game,
gd.external_game_id as external_game_id

FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.product_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth AS gpa ON original.player = gpa.login_name and gpa.game_provider_id=?
WHERE

{$sqlTime};

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if($row['status'] == 'finish'){
            $row['status'] = Game_logs::STATUS_SETTLED;
        }else{
            $row['status'] = Game_logs::STATUS_CANCELLED;
        }
    }
    
    private function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['orig_game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);

	}


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        
        $this->utils->debug_log("ICONIC SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow)");

        $result_amount = ($row['win'] - $row['bet_amount'])/$this->game_conversion_rate;
		$result_amount = $result_amount;
		$bet_amount = $row['bet_amount']/$this->game_conversion_rate;	

        if(empty($row['md5_sum'])){            
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

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
                'bet_amount' => $bet_amount, 
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount, 
                'real_betting_amount' => $bet_amount,
                'win_amount' => null, 
                'loss_amount' => null, 
                'after_balance' => null,
                'before_balance' => null
            ],
            'date_info'=>[
                'start_at' => $this->gameTimeToServerTime($row['orig_created_at']), 
                'end_at' => $this->gameTimeToServerTime($row['orig_updated_at']), 
                'bet_at' => $this->gameTimeToServerTime($row['orig_created_at']),
                'updated_at' => $row['updated_at']
            ],
            'flag'=>Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0, 
                'external_uniqueid' => $row['external_unique_id'], 
                'round_number' => $row['orig_id'],
                'md5_sum' => $row['md5_sum'], 
                'response_result_id' => $row['response_result_id'], 
                'sync_index' => $row['sync_index'],
                'bet_type' => null 
            ],
            'bet_details' => [],
            'extra' => [],            
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function getSeamlessTransactionTable(){     
        return $this->original_transactions_table;
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
                if($transaction['amount']>0){
                    $temp_game_record['amount'] = abs($transaction['amount'])/$this->game_conversion_rate;
                }
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

}//end of class
