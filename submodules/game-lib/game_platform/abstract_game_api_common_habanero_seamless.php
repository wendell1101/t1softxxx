<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';
/**
* Game Provider: Habanero
* Game Type: Slots
* Wallet Type: Seamless
* Asian Brand:
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @bermar.php.ph

    Related File
    -routes.php
    -habanero_service_api.php
**/

abstract class Abstract_game_api_common_habanero_seamless extends Abstract_game_api {
    use Year_month_table_module;

    const MD5_FIELDS_FOR_ORIGINAL = ['bet_amount','real_bet_amount','result_amount','balance_after','balance_before','status'];
    const MD5_FLOAT_AMOUNT_FIELDS = ['bet_amount','real_bet_amount','result_amount','balance_after','balance_before'];
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance'];


    const URI_MAP = array(
        self::API_queryGameListFromGameProvider => '/GetGames',
        self::API_queryBetDetailLink => '/GetGameReplayUrl',
    );

    const METHOD_MAP = array(
        self::API_queryGameListFromGameProvider => 'POST',
    );

    const GET                       = 'GET';
    const POST                      = 'POST';
    const METHOD_GET                = 'GET';
	const METHOD_POST               = 'POST';
	const METHOD_PUT               = 'PUT';

    public $api_url;
    public $currentMethod = null;
    public $ws_url = null;
    public $method;
    public $original_gamelogs_table;
    public $original_transactions_table;
    public $initialize_monthly_transactions_table;
    public $use_monthly_transactions_table;
    public $force_check_previous_transactions_table;
    public $force_check_other_transactions_table;
    public $previous_table;

	const REQUEST_TYPES = [
		'playerdetailrequest',
		'fundtransferrequest',
		'queryrequest',
		'configdetailrequest',
		'playerendsessionrequest'
	];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->api_url = $this->getSystemInfo('api_url', 'https://ws-test.insvr.com/jsonapi'); //staging: https://ws-test.insvr.com/jsonapi, live: https://ws-a.insvr.com/jsonapi
        $this->pass_key = $this->getSystemInfo('key');
        $this->brand_id = $this->getSystemInfo('brand_id');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');
        $this->currency = $this->getSystemInfo('currency');
        $this->use_transaction = $this->getSystemInfo('use_transaction', true);
        $this->use_unmerge_transaction = $this->getSystemInfo('use_unmerge_transaction', false);
        $this->save_response_result = $this->getSystemInfo('save_response_result', false);
        $this->config = json_encode($this->getSystemInfo('config', []), true);
		$this->lobby_key = $this->getSystemInfo('lobby_key', '');
        $this->use_referrer = $this->getSystemInfo('use_referrer', true);
        $this->use_new_token = $this->getSystemInfo('use_new_token', false);
        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');
        $this->language = $this->getSystemInfo('language', 'en');
        $this->lang = $this->getSystemInfo('lang','en');
        $this->force_language = $this->getSystemInfo('force_language', 'en');
        $this->ws_url = $this->getSystemInfo('ws_url');
        $this->api_key = $this->getSystemInfo('api_key');
        $this->use_default_if_empty_bet_detail_link = $this->getSystemInfo('use_default_if_empty_bet_detail_link', true);
        $this->support_bet_detail_link = $this->getSystemInfo('support_bet_detail_link', true);

        // initiate year month table
        $this->ymt_init();
    }

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        // $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', false);
        // $this->use_monthly_game_logs_table = $this->getSystemInfo('use_monthly_game_logs_table', false);

        $this->ymt_initialize($this->original_transactions_table, $this->use_monthly_transactions_table ? $this->use_monthly_transactions_table : $this->initialize_monthly_transactions_table);

        if ($this->use_monthly_transactions_table) {
            $this->original_transactions_table = $this->ymt_get_current_year_month_table();
            $this->previous_table = $this->ymt_get_previous_year_month_table();
        }
        // end monthly tables
    }

    /**
     * Helper functions
     */

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    public function getPassKey()
    {
        return $this->pass_key;
    }

    public function getBrandID()
    {
        return $this->brand_id;
    }

	public function verifyRequestType($request_type){
		if(in_array($request_type, self::REQUEST_TYPES)){
			return true;
		}
		return false;
    }

    public function buildUrl($url, $data){
        $data_string= implode('', $data);
        $params = $data;
        $params = http_build_query($params);
        $url = $url."?".$params;
        return $url;
    }

    public function getHttpHeaders($params) {
        $http_header = [
            'Content-Type' => 'application/json',
        ];

        return $http_header;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    }

    private function customHttpCall2($url, $params = [])
    {
        $this->utils->debug_log("HB SEAMLESS: (customHttpCall2)", [
            'url' => $url,
            'params' => $params
        ]);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set the request method to POST
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Pass the POST fields
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
    
        // Set proxy if defined in environment
        if (!empty($_SERVER['HTTP_PROXY'])) {
            curl_setopt($ch, CURLOPT_PROXY, $_SERVER['HTTP_PROXY']);
        } elseif (!empty($_SERVER['HTTPS_PROXY'])) {
            curl_setopt($ch, CURLOPT_PROXY, $_SERVER['HTTPS_PROXY']);
        }
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("cURL error: {$error}");
        }
    
        curl_close($ch);
    
        return [$response, $httpCode];
    }


    /**
     * Main functions
     */
    public function queryForwardGame($playerName, $extra){
        $this->utils->debug_log("HABANERO SEAMLESS: (queryForwardGame)" , $extra);

        $player_id = $this->getPlayerIdInPlayer($playerName);
        $agent = $this->CI->player_model->getAgentNameByPlayerId($player_id);

        // $language = $this->language;

        // if(isset($extra['language'])){
        //     $language = $extra['language'];            
        // }

        // if($this->force_language && !empty($this->force_language)){
        //     $language = $this->force_language;
        // }

        $language = $this->force_language ? $this->language : $this->getLauncherLanguage($extra['language']);

        $locale = $this->getLauncherLanguage($language);

        $lobbyurl = $this->getReturnUrl($extra);

		$keyname = @$extra['game_code'];
		if(!$keyname || $keyname=='_null'){
			$keyname = $this->lobby_key;
        }

        $token = $this->getPlayerTokenByUsername($playerName);
        if($this->use_new_token){
            $token = 'token:'.$this->generatePlayerToken($playerName);
        }

		$gameMode = isset($extra['game_mode']) ? $extra['game_mode']:null;
		if(in_array($gameMode, $this->demo_game_identifier)){
            $gameMode = 'fun';
        }else{
            $gameMode = 'real';
        }

        $data = array(
            "brandid" => $this->brand_id,
            "keyname" => $keyname,
            "token" => $token,
            "mode" => $gameMode,
            "lobbyurl" => $lobbyurl,
            "locale" => $locale,
        );

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            $this->CI->utils->debug_log("Abstract_game_api_common_habanero_seamless @queryForwardGame disabled homelink / lobbyurl from extra, new params:", $data);
            unset($data['lobbyurl']);
        }

        if($this->force_disable_home_link){
            $this->CI->utils->debug_log("Abstract_game_api_common_habanero_seamless @queryForwardGame force_disable_home_link=true unset lobbyurl, new params:", $data);
            unset($data['lobbyurl']);
        }

		if(isset($extra['external_category']) && !empty($extra['external_category'])){
            $player_id = $this->getPlayerIdInPlayer($playerName);
            $decoded_external_category = base64_decode($extra['external_category']);
			$successUpdate = $this->updateExternalCategoryForPlayer($player_id, $decoded_external_category);
			if(!$successUpdate){
				$this->CI->utils->error_log('HABANERO SEAMLESS: Error update external category.', $player_id, $decoded_external_category);
            }
            $data['segmentkey'] = $decoded_external_category;
		}

        $result = array(
            "success" => true,
            "url" => $this->buildUrl($this->game_launch_url, $data)
        );
        return $result;

        $this->utils->debug_log("HABANERO SEAMLESS: (queryForwardGame) ======> ", $data);
        $this->utils->debug_log("HABANERO EXTRAS ======> ", $extra);
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



    public function getReturnUrl($params){
        if($this->use_referrer){
            $path = $this->getSystemInfo('home_redirect_path', '');
            $url = trim(@$_SERVER['HTTP_REFERER'],'/').$path;
        }else{
            $url = $this->getHomeLink();
        }

        if (isset($params['home_link'])) {
            $url = $params['home_link'];
        }

        //extra checking for home link
        if(isset($params['extra']['home_link'])) {
            $url = $params['extra']['home_link'];
        }

        return $url;
    }

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
                case 'en':
                case 'en-US':
                case 'en_US':
                case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh-cn'; // chinese
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
            case 'pt':
            case 'pt-br':
                $lang = 'pt'; // portuquese
                break;
            case Language_function::INT_LANG_JAPANESE:
                case 'ja':
                case 'ja-JP':
                case 'ja-jp':
                case 'ja-JA':
                case 'ja_JA':
                case 'ja-ja':
                case 'ja_ja':
                $lang = 'ja'; // japanese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
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

        if($success){
            if(isset($extra['external_category']) && !empty($extra['external_category'])){
                $segmentKey = trim($extra['external_category']);
                $successUpdate = $this->updateExternalCategoryForPlayer($playerId, $segmentKey);
				if(!$successUpdate){
					$this->CI->utils->error_log('HABANERO SEAMLESS: (createPlayer) Error update external category.', $playerId, $segmentKey);
				}
            }
        }

        return array("success" => $success, "message" => $message);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("HABANERO SEAMLESS: (depositToGame)");

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
        $this->utils->debug_log("HABANERO SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function generateUrl($apiName, $params) {
        $uri_map = self::URI_MAP[$apiName];
        /* if($apiName != self::API_queryGameListFromGameProvider){
            return $this->returnUnimplemented();
        }else{
            // $this->api_url = $this->parseGameLaunchUrlAndGetBaseurl($this->game_launch_url);
            $url = $this->ws_url . '/' . self::URI_MAP[self::API_queryGameListFromGameProvider];
        } */

        $url = $this->api_url . $uri_map;

        $this->CI->utils->debug_log('HB_SEAMLESS GAME URL =============>' . $url);
        return $url;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function processResultForQueryTransaction($params) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName){
        $this->utils->debug_log("HABANERO SEAMLESS: (queryPlayerBalance)");

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function syncOriginalGameLogs($token = false){

        if($this->use_transaction){
            return $this->returnUnimplemented();
        }

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        //get affected game instance ids by date
        $gameInstanceIds = $this->queryGameInstanceIdsByDate($startDate, $endDate);

        //get gamerecords from transactions by gameInstanceIds
		$gameRecords = $this->queryBetTransactionsByGameInstanceIds($gameInstanceIds);

        if(!empty($gameRecords)){

            $this->processGameRecords($gameRecords, $gameInstanceIds);

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
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            $dataResult['data_count'] = count($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
        }
        return array(true, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $gameInstanceIds){
        $temp_game_records = [];

        if(!empty($gameRecords) && !empty($gameInstanceIds)){
            foreach($gameInstanceIds as $gameInstanceId){

                $temp_game_record = [];
                $temp_game_record['is_valid_game_logs'] = 0;
                $temp_game_record['is_retry'] = 0;
                $temp_game_record['is_refund'] = 0;
                $temp_game_record['is_recredit'] = 0;
                $temp_game_record['is_retry'] = 0;
                $temp_game_record['game_instance_id'] = $gameInstanceId;
                $temp_game_record['external_unique_id'] = $gameInstanceId;
                $temp_game_record['transaction_id'] = '';
                $temp_game_record['transfer_id'] = '';
                $temp_game_record['bet_amount'] = 0;
                $temp_game_record['real_bet_amount'] = 0;
                $temp_game_record['result_amount'] = 0;
                $temp_game_record['status'] = Game_logs::STATUS_SETTLED;
                $temp_game_record['start_at'] = '0000-00-00 00:00:00';
                $temp_game_record['end_at'] = '0000-00-00 00:00:00';
                $temp_game_record['real_bet_amount'] = 0;
                $temp_game_record['balance_after'] = 0;
                $temp_game_record['balance_before'] = 0;
                //$this->utils->debug_log("HABANERO SEAMLESS: (gameInstanceId)". $gameInstanceId);

                $total_credit = 0;
                $total_debit = 0;

                $current_transaction_id = 0;

                $first_index = 0;
                $last_index = 0;

                foreach($gameRecords as $index => $record) {

                    if($gameInstanceId == $record['game_instance_id']){
                        $temp_game_record['transaction_id'] = $gameInstanceId;
                        $temp_game_record['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                        $temp_game_record['game_name'] = isset($record['game_name']) ? $record['game_name'] : null;
                        $temp_game_record['game_user_name'] = isset($record['game_user_name']) ? $record['game_user_name'] : null;
                        $temp_game_record['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                        $temp_game_record['currency_code'] = isset($record['currency_code']) ? $record['currency_code'] : null;
                        $temp_game_record['transfer_id'] = null;
                        $temp_game_record['note'] = isset($record['note']) ? $record['note'] : null;;

                        if($record['gamestatemode']==1){
                            $temp_game_record['bet_amount']+= abs($record['fundinfo_amount']);
                        }

                        if($record['fundinfo_amount']<0){
                            $total_debit+= abs($record['fundinfo_amount']);
                        }

                        if($record['fundinfo_amount']>0){
                            $total_credit+= abs($record['fundinfo_amount']);
                        }

                        if($record['is_refund']){
                            $temp_game_record['status'] = Game_logs::STATUS_CANCELLED;
                        }

                        $data['response_result_id'] = null;


                        $temp_start_at = isset($record['start_at']) ? $record['start_at'] : '0000-00-00 00:00:00';
                        $temp_end_at = isset($record['end_at']) ? $record['end_at'] : '0000-00-00 00:00:00';

                        if($first_index == 0 || $record['sync_index'] < $first_index ){
                            $first_index = $record['sync_index'];
                            $temp_game_record['start_at'] = $temp_start_at;
                            $temp_game_record['balance_before'] = $record['balance_before'];
                        }

                        if($record['sync_index'] > $last_index){
                            $last_index = $record['sync_index'];
                            $temp_game_record['end_at'] = $temp_end_at;
                            $temp_game_record['balance_after'] = $record['balance_after'];
                        }

                        if($record['refund_identifier']>0){
                            $temp_game_record["status"] = Game_logs::STATUS_CANCELLED;
                        }

                    }

                }//end foreach gamerecords

                $temp_game_record['start_at'] = $this->gameTimeToServerTime($temp_game_record['start_at']);
                $temp_game_record['end_at'] = $this->gameTimeToServerTime($temp_game_record['end_at']);

                $temp_game_record['result_amount'] = $total_credit - $temp_game_record['bet_amount'];

                $temp_game_record['real_bet_amount'] = $temp_game_record['bet_amount'];

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }//end foreach gameinstance id
        }

        $gameRecords = $temp_game_records;
    }

    public function processGameRecordsFromTrans(&$gameRecords, $gameInstanceIds){
        $temp_game_records = [];

        if(!empty($gameRecords) && !empty($gameInstanceIds)){
            foreach($gameInstanceIds as $gameInstanceId){

                $temp_game_record = [];

                $temp_game_record['game_instance_id'] = $gameInstanceId;
                $temp_game_record['round_number'] = $gameInstanceId;
                $temp_game_record['external_unique_id'] = $gameInstanceId;
                $temp_game_record['external_uniqueid'] = $gameInstanceId;
                $temp_game_record['transaction_id'] = '';
                $temp_game_record['transfer_id'] = '';
                $temp_game_record['bet_amount'] = 0;
                $temp_game_record['real_bet_amount'] = 0;
                $temp_game_record['result_amount'] = 0;
                $temp_game_record['status'] = Game_logs::STATUS_SETTLED;
                $temp_game_record['start_at'] = '0000-00-00 00:00:00';
                $temp_game_record['end_at'] = '0000-00-00 00:00:00';
                $temp_game_record['real_bet_amount'] = 0;
                $temp_game_record['balance_after'] = 0;
                $temp_game_record['balance_before'] = 0;
                //$this->utils->debug_log("HABANERO SEAMLESS: (gameInstanceId)". $gameInstanceId);

                $total_credit = 0;
                $total_debit = 0;

                $current_transaction_id = 0;

                $first_index = 0;
                $last_index = 0;

                $dataComplete = false;

                foreach($gameRecords as $index => $record) {

                    if($gameInstanceId == $record['game_instance_id']){
                        $dataComplete = true;

                        $temp_game_record['player_username'] = $record['game_user_name'];
                        $temp_game_record['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                        $temp_game_record['game_type_id'] = isset($record['game_type_id']) ? $record['game_type_id'] : null;
                        $temp_game_record['game_description_id'] = isset($record['game_description_id']) ? $record['game_description_id'] : null;
                        $temp_game_record['game'] = isset($record['game_name']) ? $record['game_name'] : null;

                        $temp_game_record['transaction_id'] = $gameInstanceId;
                        $temp_game_record['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                        $temp_game_record['game_name'] = isset($record['game_name']) ? $record['game_name'] : null;
                        $temp_game_record['game_user_name'] = isset($record['game_user_name']) ? $record['game_user_name'] : null;
                        $temp_game_record['game'] = $temp_game_record['game_name'];
                        $temp_game_record['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                        $temp_game_record['currency_code'] = isset($record['currency_code']) ? $record['currency_code'] : null;
                        $temp_game_record['transfer_id'] = null;
                        $temp_game_record['response_result_id'] = null;
                        $temp_game_record['note'] = isset($record['note']) ? $record['note'] : null;

                        if($record['gamestatemode']==1){
                            $temp_game_record['bet_amount']+= abs($record['fundinfo_amount']);
                        }

                        if($record['fundinfo_amount']<0){
                            $total_debit+= abs($record['fundinfo_amount']);
                        }

                        if($record['fundinfo_amount']>0){
                            $total_credit+= abs($record['fundinfo_amount']);
                        }

                        if($record['is_refund']){
                            $temp_game_record['status'] = Game_logs::STATUS_REFUND;
                        }


                        $temp_start_at = isset($record['start_at']) ? $record['start_at'] : '0000-00-00 00:00:00';
                        $temp_end_at = isset($record['end_at']) ? $record['end_at'] : '0000-00-00 00:00:00';

                        if($first_index == 0 || $record['sync_index'] < $first_index ){
                            $first_index = $record['sync_index'];
                            $temp_game_record['start_at'] = $temp_start_at;
                            $temp_game_record['balance_before'] = $record['balance_before'];
                        }

                        if($record['sync_index'] > $last_index){
                            $last_index = $record['sync_index'];
                            $temp_game_record['end_at'] = $temp_end_at;
                            $temp_game_record['balance_after'] = $record['balance_after'];

                        }

                        if($record['refund_identifier']>0){
                            $temp_game_record["status"] = Game_logs::STATUS_CANCELLED;
                        }

                    }

                }//end foreach gamerecords

                $temp_game_record['bet_at'] = $temp_game_record['start_at'] = $this->gameTimeToServerTime($temp_game_record['start_at']);
                $temp_game_record['end_at'] = $this->gameTimeToServerTime($temp_game_record['end_at']);

                $temp_game_record['result_amount'] = $total_credit - $total_debit;

                $temp_game_record['bet_amount'] = $temp_game_record['real_bet_amount'] = $total_debit;

                $temp_game_record['after_balance'] = $temp_game_record['balance_after'];

                $temp_game_record['md5_sum'] = '';

                if($dataComplete){
                    $temp_game_records[] = $temp_game_record;
                }

                unset($temp_game_record);
            }//end foreach gameinstance id
        }

        $gameRecords = $temp_game_records;
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

        if($this->use_transaction){

            if($this->use_unmerge_transaction){
                return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTransUnmerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
            }


            return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabled_game_logs_unsettle);
        }else{
            return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
        }
    }

    public function queryOriginalGameLogsFromTransUnmerge($dateFrom, $dateTo, $use_bet_time){
        $where=' t.updated_at >= ? and t.updated_at <= ?';

        if ($this->use_monthly_transactions_table) {
            $this->original_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }

$sql = <<<EOD
SELECT t.id as sync_index,
t.accountid as player_username,
t.gamedetails_name as game,
t.gamedetails_name as game_name,
t.gamedetails_keyname as game_code,
t.friendlygameinstanceid as round_number,
t.fundinfo_dtevent_parsed as start_at,
t.fundinfo_dtevent_parsed as end_at,
t.fundinfo_dtevent_parsed as bet_at,
t.response_result_id as response_result_id,
t.fundinfo_transferid as external_unique_id,
t.fundinfo_transferid as external_uniqueid,
t.balance_after as after_balance,
t.balance_after as balance_after,
t.balance_before as balance_before,
t.trans_type as trans_type,
t.is_refunded as is_refunded,
t.isrefund as is_refund,
IF(fundinfo_amount<0,t.fundinfo_amount,0) bet_amount,
IF(fundinfo_amount<0,t.fundinfo_amount,0) real_bet_amount,
IF(fundinfo_amount>=0,t.fundinfo_amount,0) result_amount,
t.is_valid_transaction as status,
t.updated_at as updated_at,
null as md5_sum,
t.description as note,

t.player_id as player_id,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM {$this->original_transactions_table} as t

LEFT JOIN game_description as gd ON gd.external_game_id = t.keyname AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gt.id = gd.game_type_id
JOIN game_provider_auth ON game_provider_auth.player_id = t.player_id AND game_provider_auth.game_provider_id=?

WHERE
{$where}
ORDER BY t.friendlygameinstanceid, t.fundinfo_dtevent_parsed;

EOD;

$params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom, $dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        //get affected game instance ids by date
        $gameInstanceIds = $this->queryGameInstanceIdsByDate($dateFrom, $dateTo);
        //$this->utils->debug_log("HABANERO SEAMLESS: (queryOriginalGameLogsFromTrans)", $gameInstanceIds);

        //get gamerecords from transactions by gameInstanceIds
        $gameRecords = $this->queryBetTransactionsByGameInstanceIds($gameInstanceIds);

        $this->processGameRecordsFromTrans($gameRecords, $gameInstanceIds);

        return $gameRecords;
    }


    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        //$this->utils->debug_log("HABANERO SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow)");

        if($this->use_unmerge_transaction){


        }else{
            $row['win_amount'] = $row['loss_amount'] = 0;
            if($row['result_amount']>0){
                $row['win_amount'] = abs($row['result_amount'] - $row['bet_amount']);
            }else{
                $row['loss_amount'] = abs($row['result_amount'] - $row['bet_amount']);
            }
        }

        $row['after_balance'] = $row['balance_after'];
        if(empty($row['md5_sum'])){
            $this->CI->utils->debug_log('HABANERO SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow=>generateMD5SumOneRow)');
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $extra_info = array(
            "note" => isset($row['note']) ? $row['note'] : "",
        );

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
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info'=>[
                'start_at' => $this->gameTimeToServerTime($row['start_at']),
                'end_at' => $this->gameTimeToServerTime($row['end_at']),
                'bet_at' => $this->gameTimeToServerTime($row['bet_at']),
                'updated_at' => $this->utils->getNowForMysql(),
            ],
            'flag'=>Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra_info,
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $gameRoundResult = $this->getPlayerGameRoundResult($row['player_id'], $row['round_number']);
        $row['bet_details'] = $this->buildBetDetails($row, $gameRoundResult);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime='hs.updated_at >= ? and hs.updated_at <= ?';
        $sql = <<<EOD
SELECT hs.id as sync_index,
hs.game_user_name as player_username,
hs.game_name as game,
hs.game_code as game_code,
hs.game_instance_id as round_number,
hs.start_at,
hs.end_at,
hs.response_result_id,
hs.external_unique_id,
hs.external_unique_id as external_uniqueid,
hs.bet_amount,
hs.result_amount,
hs.real_bet_amount,
hs.start_at as bet_at,
hs.start_at,
hs.created_at,
hs.updated_at,
hs.md5_sum,
hs.status,
hs.balance_after after_balance,
hs.balance_before,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM habanero_seamless_game_logs as hs
LEFT JOIN game_description as gd ON hs.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON hs.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

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

        if($this->use_unmerge_transaction){
            if((isset($row["is_refunded"]) && $row["is_refunded"]==1) || (isset($row["is_refund"]) && $row["is_refund"]==1)){
                $row["status"] =Game_logs::STATUS_REFUND;
            }else{
                $row["status"] =Game_logs::STATUS_SETTLED;
            }

        }

        $gameRoundResult = $this->getPlayerGameRoundResult($row['player_id'], $row['round_number']);
        $row['bet_details'] = $this->buildBetDetails($row, $gameRoundResult);
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
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
	}


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $this->utils->debug_log("HABANERO SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow)");

        if($this->use_unmerge_transaction){
            $row['bet_amount'] =  abs($row['bet_amount']);
            $row['real_bet_amount'] =  abs($row['real_bet_amount']);
            $row['result_amount'] =  abs($row['result_amount']) - $row['bet_amount'];
        }

        if(empty($row['md5_sum'])){
            $this->CI->utils->debug_log('HABANERO SEAMLESS: (makeParamsForInsertOrUpdateGameLogsRow=>generateMD5SumOneRow)');
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
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
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
                'external_uniqueid' => $row['external_unique_id'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }


    public function queryGameInstanceIdsByDate($dateFrom, $dateTo){
        if ($this->use_monthly_transactions_table) {
            $this->original_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }

        $sqlTime='t.updated_at >= ? and t.updated_at <= ? and t.isrefund = 0';
        $sql = <<<EOD
SELECT DISTINCT t.friendlygameinstanceid as gameinstanceid
FROM {$this->original_transactions_table} as t
WHERE
{$sqlTime};
EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $data = [];
        foreach($result as $row){
            $data[] = $row['gameinstanceid'];
        }

        return $data;
    }

    public function queryBetTransactionsByGameInstanceIds($gameInstanceIds){
        if(empty($gameInstanceIds)){
            $gameInstanceIds = ['0'];
        }
        $gameInstanceIds = implode("','", $gameInstanceIds);
        $where="t.friendlygameinstanceid IN ('$gameInstanceIds')";
        $params = [];

        if($this->use_transaction){
            $result = $this->queryBetTransactionsFromTrans($where, $params, $this->original_transactions_table);

            if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($result)) {
                    $result = $this->queryBetTransactionsFromTrans($where, $params, $this->previous_table);
                }
            }
        }else{
            $result = $this->queryBetTransactions($where, $params, $this->original_transactions_table);

            if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($result)) {
                    $result = $this->queryBetTransactions($where, $params, $this->previous_table);
                }
            }
        }

        return $result;
    }

    public function queryBetTransactionsByDate($dateFrom, $dateTo){
        $where=' t.updated_at >= ? and t.updated_at <= ? and t.isrefund = 0';
        $params = [$dateFrom, $dateTo];

        if ($this->use_monthly_transactions_table) {
            $this->original_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }

        if($this->use_transaction){
            $result = $this->queryBetTransactionsFromTrans($where, $params, $this->original_transactions_table);

            if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($result)) {
                    $result = $this->queryBetTransactionsFromTrans($where, $params, $this->previous_table);
                }
            }
        }else{
            $result = $this->queryBetTransactions($where, $params, $this->original_transactions_table);

            if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($result)) {
                    $result = $this->queryBetTransactions($where, $params, $this->previous_table);
                }
            }
        }

        return $result;
    }

    public function queryBetTransactions($where, $params = [], $table_name = null){
        if($where){
            $where = ' AND ' . $where;
        }

        if (empty($table_name)) {
            $table_name = $this->original_transactions_table;
        }

$sql = <<<EOD
SELECT t.id as sync_index,
t.keyname as game_code,
t.gamedetails_name as game_name,
t.friendlygameinstanceid as game_instance_id,
t.accountid as game_user_name,
t.player_id as player_id,
t.fundinfo_currencycode as currency_code,
t.friendlygameinstanceid as transaction_id,
t.fundinfo_transferid as transfer_id,
t.fundinfo_amount as fundinfo_amount,
t.is_refunded as refund_identifier,

t.balance_after as balance_after,
t.balance_before as balance_before,

t.fundinfo_dtevent_parsed as start_at,
t.fundinfo_dtevent_parsed as end_at,

t.response_result_id as response_result_id,
t.friendlygameinstanceid as external_unique_id,
t.friendlygameinstanceid as external_uniqueid,
t.is_refunded as is_refunded,
t.isrefund as is_refund,
t.fundinfo_isbonus as is_bonus,
t.description as note,

t.fundinfo_gamestatemode as gamestatemode

FROM {$table_name} as t
WHERE 1
{$where}
ORDER BY t.friendlygameinstanceid, t.fundinfo_dtevent_parsed;
EOD;

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function queryBetTransactionsFromTrans($where, $params = [], $table_name = null){
        if($where){
            $where = ' AND ' . $where;
        }

        if (empty($table_name)) {
            $table_name = $this->original_transactions_table;
        }

$sql = <<<EOD
SELECT t.id as sync_index,
t.keyname as game_code,
t.gamedetails_name as game_name,
t.friendlygameinstanceid as game_instance_id,
t.player_id as player_id,
t.fundinfo_currencycode as currency_code,
t.friendlygameinstanceid as transaction_id,
t.fundinfo_transferid as transfer_id,
t.fundinfo_amount as fundinfo_amount,
t.is_refunded as refund_identifier,

t.balance_after as balance_after,
t.balance_before as balance_before,

t.fundinfo_dtevent_parsed as start_at,
t.fundinfo_dtevent_parsed as end_at,

t.response_result_id as response_result_id,
t.friendlygameinstanceid as external_unique_id,
t.friendlygameinstanceid as external_uniqueid,
t.is_refunded as is_refunded,
t.isrefund as is_refund,
t.fundinfo_isbonus as is_bonus,

t.fundinfo_gamestatemode as gamestatemode,
t.description as note,

game_provider_auth.player_id,
game_provider_auth.login_name game_user_name,
gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM {$table_name} as t

LEFT JOIN game_description as gd ON gd.external_game_id = t.keyname AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gt.id = gd.game_type_id
JOIN game_provider_auth ON game_provider_auth.player_id = t.player_id AND game_provider_auth.game_provider_id=?

WHERE 1
{$where}
ORDER BY t.friendlygameinstanceid, t.fundinfo_dtevent_parsed;

EOD;

$params=[$this->getPlatformCode(), $this->getPlatformCode()];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        if ($this->use_monthly_transactions_table) {
            $this->original_transactions_table = $this->ymt_get_year_month_table_by_date(null, $startDate);
        }

$sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.fundinfo_amount as amount,
t.balance_after as after_balance,
t.balance_before as before_balance,
t.friendlygameinstanceid as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type trans_type
FROM {$this->original_transactions_table} as t
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.created_at asc,t.id asc;

EOD;

$params=[$startDate, $endDate];

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
                $extra = ['trans_type'=>$transaction['trans_type']];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if($transaction['amount']<0){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='T.created_at >= ? AND T.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $transTable = $this->getTransactionsTable(); 
        $gameRoundEnd = 2;   #docs

        $sql = <<<EOD
SELECT 
T.friendlygameinstanceid, 
T.friendlygameinstanceid as round_id, 
T.gamedetails_gameinstanceid, 
T.friendlygameinstanceid as transaction_id,
T.trans_type, 
T.funds_debitandcredit, 
T.fundinfo_gamestatemode, 
T.fundinfo_initialdebittransferid,
T.fundinfo_transferid,
GD.game_platform_id,
T.keyname
FROM {$transTable} AS T
LEFT JOIN game_description AS GD ON GD.game_code = T.keyname
WHERE NOT EXISTS (
        SELECT 'exists'
        FROM {$transTable} AS T2
        WHERE T2.friendlygameinstanceid = T.friendlygameinstanceid
        AND T2.fundinfo_gamestatemode = ?
    )
AND {$sqlTime}
EOD;

        $params=[
            $gameRoundEnd,
            $dateFrom,
            $dateTo
		];

        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('HB SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $transTable = $this->getTransactionsTable(); 

        $roundId = $data['friendlygameinstanceid'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
T.external_uniqueid,
T.created_at as transaction_date,
T.trans_type as transaction_type,
T.player_id,
T.fundinfo_transferid as round_id,
T.fundinfo_transferid as transaction_id,
ABS(SUM(T.fundinfo_amount)) as amount,
ABS(SUM(T.fundinfo_amount)) as deducted_amount,
GD.id as game_description_id,
GD.game_type_id,
GD.game_platform_id
FROM {$transTable} as T
LEFT JOIN game_description AS GD ON GD.game_code = T.keyname
WHERE T.friendlygameinstanceid=?
AND GD.game_platform_id=?
EOD;
 
        $params=[ $roundId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;
                
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('HABANERO SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $transTable = $this->getTransactionsTable();
        $gameLogsTable = $this->original_gamelogs_table; 

        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
T.created_at as transaction_date,
T.trans_type as transaction_type,
T.player_id,
T.friendlygameinstanceid, 
T.fundinfo_transferid as transaction_id,
T.fundinfo_amount,
T.external_uniqueid,
GD.game_code,
GD.game_platform_id
FROM {$transTable} as T
LEFT JOIN game_description AS GD 
ON GD.game_code = T.keyname
WHERE NOT EXISTS (
        SELECT 'exists'
        FROM {$transTable} as T2
        WHERE T2.friendlygameinstanceid = T.friendlygameinstanceid
        AND T2.fundinfo_gamestatemode = 2
    )
AND T.external_uniqueid=?
AND GD.game_platform_id=?

EOD;
     
        $params=[$external_uniqueid, $game_platform_id];
        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        if(!empty($trans)){
            return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
        }
        
        return array('success'=>true, 'status'=>Game_logs::STATUS_SETTLED);
    }

    public function queryGameListFromGameProvider($extra = null)
	{
        $this->currentMethod = self::API_queryGameListFromGameProvider;
        $this->method = 'POST';

		$this->utils->debug_log("HB SEAMLESS: (queryGameListFromGameProvider)");   

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryGameListFromGameProvider',
		);
		$params = [
			'BrandId' => $this->brand_id,	
			'APIKey' => $this->api_key,
		];
		return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
	}

    public function processResultForQueryGameListFromGameProvider($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		$resultJson = $this->getResultJsonFromParams($params);
		if ($resultJson == null) {
			$this->CI->utils->debug_log('processResultForQueryGameListFromGameProvider returned null');
			return array(false, null);
		}

		$status = !empty($resultJson);
		$success = $this->processResultBoolean($responseResultId, $status);
		return array($success, $resultJson);
	}

    protected function processResultBoolean($responseResultId, $result, $playerName = null,$statusCode=200) {
		$success = false;
		if ($result) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('HB got error', $responseResultId, 'playerName', $playerName, 'result', $result);
		}

		return $success;
	}

    public function rebuildGameList($games) {
        $data = [];

        if( isset($games['Games']) && !empty($games['Games']) ){
            foreach ($games as $game) {
                $newGame = [];
                $external_uniqueid = isset($game['KeyName']) ? $game['KeyName'] . '-' . $this->getPlatformCode() : '';
    
                $newGame['game_platform_id']  = $this->getPlatformCode();
                $newGame['game_code'] 		  = isset($game['KeyName']) ? $game['KeyName'] : '';
                $newGame['json_fields'] 	  = !empty($game) ? json_encode($game) : '';
                $newGame['external_uniqueid'] = isset($external_uniqueid) ? $external_uniqueid : '';
                $data[] = $newGame;
            }
        }
       
        return $data;
    }

    private function parseGameLaunchUrlAndGetBaseurl($toParseUrl){
        # Sample URL: https://test-url.com/some/path?query=string
        $parsedUrl = parse_url($toParseUrl);
        $baseUrl = $parsedUrl['scheme'] . "://" . $parsedUrl['host'] . "/";
        return  $baseUrl; #ss Output: https://test-url.com/

    }

    public function getPlayerGameRoundResult($playerId, $roundId) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $select = [
            'trans_type',
            'ABS(fundinfo_amount) AS amount',
            'balance_after AS after_balance',
            'isRefund',
        ];

        $where = [
            'player_id' => $playerId,
            'friendlygameinstanceid' => $roundId,
        ];

        $transactions = $this->CI->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_transactions_table, $where, $select);

        if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($transactions)) {
                $transactions = $this->CI->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->previous_table, $where, $select);
            }
        }

        $result = [
            'valid_bet_amount' => 0,
            'bet_amount' => 0,
            'win_amount' => 0,
            'refund_amount' => 0,
            'result_amount' => 0,
            'after_balance' => 0,
        ];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                if ($transaction['isRefund']) {
                    $result['refund_amount'] += $transaction['amount'];
                    $result['after_balance'] = $transaction['after_balance'];
                } else {
                    $result['valid_bet_amount'] += $transaction['amount'];
                    $result['bet_amount'] += $transaction['amount'];

                    if ($transaction['trans_type'] == 'credit') {
                        $result['win_amount'] += $transaction['amount'];
                        $result['after_balance'] = $transaction['after_balance'];
                    }
                }

                if ($transaction['isRefund']) {
                    $result['result_amount'] = -$result['refund_amount'];
                } else {
                    $result['result_amount'] = $result['win_amount'] - $result['bet_amount'];
                }
            }
        }

        return $result;
    }

    private function buildBetDetails($transaction, $gameRoundResult) {
        $betDetails = [
            'game_name' => $transaction['game_name'],
            'round_id' => $transaction['round_number'],
            'valid_bet_amount' => $gameRoundResult['valid_bet_amount'],
            'bet_amount' => $gameRoundResult['bet_amount'],
            'result_amount' => $gameRoundResult['result_amount'],
            'betting_datetime' => $transaction['start_at'],
            'settlement_datetime' => $transaction['end_at'],
        ];
    
        if ($transaction['status'] == Game_logs::STATUS_REFUND) {
            $betDetails['refund_amount'] = $gameRoundResult['refund_amount'];
        } else {
            $betDetails['win_amount'] = $gameRoundResult['win_amount'];
        }
    
        return $betDetails;
    }

    public function queryBetDetailLink($playerUsername, $externalUniqueId = null, $extra = null) {
        $this->method = self::METHOD_POST;
        $this->currentMethod = self::API_queryBetDetailLink;
        $roundId = $externalUniqueId;

        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $externalUniqueId, $extra);
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'playerUsername' => $playerUsername,
        ];

        if ($this->use_unmerge_transaction) {
            $this->CI->load->model(['original_seamless_wallet_transactions']);

            $where = [
                'external_uniqueid' => $externalUniqueId,
            ];

            $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_transactions_table, $where);

            if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($transaction)) {
                    $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, $where);
                }
            }

            if (!empty($transaction)) {
                $roundId = $transaction['friendlygameinstanceid'];
            }
        }

        $params = [
            'BrandId' => $this->brand_id,
            'APIKey' => $this->api_key,
            'FriendlyId' => $roundId,
        ];

        $result = $this->callApi(self::API_queryBetDetailLink, $params, $context);

        if (empty($result['url']) && $this->use_default_if_empty_bet_detail_link) {
            // return $this->getDefaultBetDetailLink($externalUniqueId);
            return $this->getBetDetailLinkWithToken($playerUsername, $externalUniqueId);
        }

        return $result;
    }

    public function processResultForQueryBetDetailLink($params){
        $responseResultId = $this->getResponseResultIdFromParams((array) $params);
        $resultJson = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, !empty($resultJson));

        $result = [
            'url' => null,
        ];

        if ($success) {
            $result['url'] = !empty($resultJson['ReplayUrl']) ? $resultJson['ReplayUrl'] : null;
        } else {
            $this->CI->utils->debug_log('processResultForQueryBetDetailLink returned null');
        }

        return array($success, $result);
    }
}//end of class
