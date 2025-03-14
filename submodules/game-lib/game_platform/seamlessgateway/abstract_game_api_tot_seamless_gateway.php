<?php

require_once dirname(__FILE__) . '/../abstract_game_api.php';

abstract class Abstract_game_api_tot_seamless_gateway extends Abstract_game_api{

    const CODE_SUCCESS=0;
    const CODE_INVALID_SIGN=1;
    const CODE_INVALID_AUTH_TOKEN=2;
    const CODE_INVALID_CURRENCY=3;
    const CODE_INVALID_WHITE_IP=4;
    const CODE_NO_PERMISSION=5;
    //system
    const CODE_INVALID_REQUEST=9997;
    const CODE_LOCK_FAILED=9998;
    const CODE_INTERNAL_ERROR=9999;

    const CODE_AGENT_IS_SUSPENDED=6;
    const CODE_AGENT_IS_FROZEN=7;
    const CODE_AGENT_IS_INACTIVE=8;
    const CODE_INVALID_SECURE_KEY=9;
    const CODE_INVALID_USERNAME=10;
    const CODE_USERNAME_DOES_NOT_BELONG_MERCHANT=11;
    const CODE_INVALID_PASSWORD=12;
    const CODE_DUPLICATE_USERNAME=13;
	const CODE_INVALID_GAME_MODE=14;
	const CODE_LOAD_GAME_API_FAILED=15;
	const CODE_GAME_PLATFORM_ON_MAINTENANCE=16;
	const CODE_GAME_PLATFORM_IS_DISABLED=17;
	const CODE_INVALID_GAME_PLATFORM_ID=18;
	const CODE_NO_ACTIVE_GAME_PLATFORM_IN_AGENT=19;
	const CODE_NO_PERMISSION_ON_GAME_PLATFORM=20;
	const CODE_NOT_FOUND_GAME_UNIQUE_ID=21;
    const CODE_PLAYER_IS_BLOCKED_IN_GAME=22;
	const CODE_EXTERNAL_API_ERROR=23;
	const CODE_INVALID_FROM_DATE_TIME=24;
	const CODE_INVALID_TO_DATE_TIME=25;
	const CODE_INVALID_SIZE_PRE_PAGE=26;
	const CODE_INVALID_PAGE_NUMBER=27;
	const CODE_INVALID_GAME_HISTORY_STATUS=28;
	const CODE_INVALID_DATE_MODE=29;
	const CODE_INVALID_MULTIPLE_GAME_PLATFORM=30;
	const CODE_INVALID_BET_DETAIL_ID=31;
	const CODE_INVALID_MERCHANT_CODE=32;
	const CODE_INVALID_CATEGORY_MODE=33;
	const CODE_INVALID_IS_MOBILE=34;
	const CODE_NOT_FOUND_REMOTE_WALLET_UNIQUEID=35;
	const CODE_UNIQUEID_IS_REQUIRED=36;
	const CODE_USERNAME_NOT_EXIST=37;
	const CODE_INVALID_PLAYER_PREFIX=38;
	const CODE_INVALID_FREE_ROUND_EXPIRATION_DATE=39;
	const CODE_GAME_PLATFORM_DOESNT_SUPPORT_FREE_ROUND_BONUS=40;
	const CODE_INVALID_FREE_ROUND_TRANSACTION_ID=41;
	const CODE_GAME_PLATFORM_DOES_NOT_SUPPORT_BET_DETAIL_LINK=42;

	protected $api_url;
	protected $api_merchant_code;
	protected $api_secured_key;
	protected $api_sign_key;
	protected $api_token;
	protected $api_agent_id;
	protected $language;
	protected $currency;
	protected $prefix_for_username;
	protected $home_link;
	protected $on_error_redirect;
	protected $cashier_link;

	protected $game_launcher_currency;
	protected $mobile_logout_path;
	protected $mobile_lobby_path;
	protected $support_url;
	protected $use_www_as_dm;
	protected $query_forward_api_url;
	protected $size_per_page;
	protected $try_get_real_url;
	protected $current_player_id;
	protected $enabled_t1_directly_sync_api_list;
	protected $enabled_sync_t1_quickly;
	protected $enabled_game_logs_unsettle;
	protected $enable_sync_t1_unsettle;
	protected $bet_limit_settings;
	protected $fixed_prefix_to_be_remove;

	protected $max_allow_sync_time_interval;
	protected $method;
    public $launcher_mode;

	const API_syncUnsettledGameRecords = "syncUnsettledGameRecords";
	const API_syncMultiplePlatformGameRecords='syncMultiplePlatformGameRecords';
	const API_syncMultiplePlatformStreamGameHistory='syncMultiplePlatformStreamGameHistory';
	const API_isPlayerBlocked = 'isPlayerBlocked';
	const API_kickoutPlayer = 'kickoutPlayer';

	const URI_MAP = array(
		self::API_generateToken => 'generate_token',
		self::API_createPlayer => 'create_player',
		self::API_isPlayerExist => 'query_player_status',
		self::API_blockPlayer => 'block_player',
		self::API_unblockPlayer => 'unblock_player',
		self::API_isPlayerBlocked => 'query_player_status',
		self::API_kickoutPlayer => 'kickout_player',

		self::API_queryForwardGame => 'query_game_launcher',
		self::API_syncMultiplePlatformGameRecords => 'query_multiple_platform_game_history',
		self::API_syncMultiplePlatformStreamGameHistory=>'query_multiple_platform_stream_game_history',
		self::API_queryBetDetailLink=>'query_bet_detail_link',
		self::API_queryRemoteWalletTransaction=>'query_remote_wallet_transaction',
	);

	public function __construct()
	{
		parent::__construct();

		$this->CI->load->model(['common_token']);

		if(!$this->utils->isEnabledMDB()){
			throw new Exception('only work on mdb mode');
		}

		$this->api_url = $this->getSystemInfo('url');
		$this->api_merchant_code = $this->getSystemInfo('api_merchant_code');
		$this->api_secured_key = $this->getSystemInfo('key');
		$this->api_sign_key = $this->getSystemInfo('secret');

		$this->language = $this->getSystemInfo('language');
		$this->currency = $this->utils->getActiveCurrencyKeyOnMDB();
		$this->game_launcher_currency = $this->getSystemInfo('game_launcher_currency');
		$this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
		// $this->is_update_original_row = $this->getSystemInfo('is_update_original_row',false);
		$this->mobile_logout_path = $this->getSystemInfo('mobile_logout_path', '');
		$this->mobile_lobby_path = $this->getSystemInfo('mobile_lobby_path', '');
		$this->support_url = $this->getSystemInfo('support_url', '');
		$this->use_www_as_dm = $this->getSystemInfo('use_www_as_dm', false);

		$this->max_allow_sync_time_interval = $this->getSystemInfo('max_allow_sync_time_interval', '+5 minutes');
		$this->method = self::METHOD_POST;

		$this->size_per_page=$this->getSystemInfo('size_per_page', 5000);

		// $this->api_token_timeout_on_cache_seconds=$this->getSystemInfo('api_token_timeout_on_cache_seconds', 3600);
		$this->try_get_real_url=$this->getSystemInfo('try_get_real_url', true);

		$this->current_player_id=null;

		//it will be sync all t1 together and ignore original part
    	$this->enabled_t1_directly_sync_api_list=$this->CI->utils->getConfig('enabled_t1_directly_sync_api_list');
    	$this->enabled_sync_t1_quickly=in_array($this->getPlatformCode(), $this->enabled_t1_directly_sync_api_list);

		//enabled_game_logs_unsettle
		$this->enabled_game_logs_unsettle=$this->CI->utils->getConfig('enabled_game_logs_unsettle');
		//call unsettle api
		$this->enable_sync_t1_unsettle=$this->CI->utils->getConfig('enable_sync_t1_unsettle');

		# ONEWORKS BET LIMITS SETTINGS
        $this->bet_limit_settings = $this->getSystemInfo('bet_limit_settings');
		$this->fixed_prefix_to_be_remove = $this->utils->getConfig('game_fixed_prefix_to_be_remove'); # fixed prefix set on gateway or t1 that need to remove on t1xxx games

		// for redirection
		$this->on_error_redirect = $this->getSystemInfo('on_error_redirect', '');
    	$this->home_link = $this->getSystemInfo('home_link', $this->utils->getUrl());
    	$this->cashier_link = $this->getSystemInfo('cashier_link', '');
    	$this->disable_home_link = $this->getSystemInfo('disable_home_link', false);
    	$this->launcher_mode = $this->getSystemInfo('launcher_mode', "singleOnly");
    	$this->enabled_t1_merging_rows=$this->CI->utils->getConfig('enabled_t1_merging_rows');
	}


    public function isSeamLessGame(){
        return true;
    }

    abstract public function getOriginalPlatformCode();

	/**
	 * Generate URL
	 *
	 * @param $apiName
	 * @param $params
	 * @return string
	 */
	public function generateUrl($apiName, $params) {
		# generate signature
		$apiUri = self::URI_MAP[$apiName];

		$apiUrl=rtrim($this->api_url, '/');

		$url = $apiUrl .'/'. $apiUri.'?_r_='.random_string('md5');

		$this->utils->debug_log('apiName', $apiName,'url', $url, '====================params', $params);
		return $url;
	}


    protected function getHttpHeaders($params) {
        return ["Accept" => "application/json", 'Content-Type'=> 'application/json'];
    }

	protected function customHttpCall($ch, $params) {
		if(!isset($params['sign']) || empty($params['sign'])){
			// don't genereate twice
			// generate signature
			list($sign, $signString)=$this->CI->common_token->generateSignBoolToStr($params, $this->api_sign_key);
			$this->utils->debug_log('sign on call', $sign, 'sign string', $signString);
			$params['sign'] = $sign;
		}

		// $params['merchant_code'] = $this->api_merchant_code;
		// $params['auth_token'] = $this->auth_token;
		$data_json = json_encode($params);

		// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName=null) {
		if(!isset($resultJson['code'])){
			return false;
		}
		$success = self::CODE_SUCCESS == $resultJson['code'];
		if (!$success) {
			$this->utils->error_log('return error', $resultJson, 'responseResultId', $responseResultId);
		}
		return $success;
	}

	/**
	 * will check timeout, if timeout then call again
	 * @return token
	 */
    public function getAvailableApiToken(){
        return $this->getCommonAvailableApiToken(function(){
           $forceNew=true;
           return $this->generateToken($forceNew);
        });
    }

	/**
	 * Generate token by merchant code and secure key
	 *
	 * $forceNew boolean = will always create new token if it’s true
	 */
	public function generateToken($forceNew=false){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGenerateToken',
			'playerId'=>isset($this->current_player_id) ? $this->current_player_id : null,
		);

		$params = array(
			'merchant_code'=>$this->api_merchant_code,
			'currency' => $this->currency,
			'secure_key'=>$this->api_secured_key,
			'force_new'=>$forceNew,
		);

		$this->utils->debug_log('API_generateToken params', $params);
		return $this->callApi(self::API_generateToken, $params, $context);
	}

	public function processResultForGenerateToken($params){
		$resultArr = $this->getResultJsonFromParams($params);
		$this->utils->debug_log('API_generateToken result', $resultArr);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

		if($success){
			$api_token = $resultArr['detail']['auth_token'];
			//minus 30 seconds
			$api_token_timeout_datetime = $this->utils->getMinusSecondsForMysql($resultArr['detail']['timeout_datetime'], 30);
			$result['api_token']=$api_token;
			$result['api_token_timeout_datetime']=$api_token_timeout_datetime;
		}
		return array($success,$result);
	}

	/**
	 * Create Player
	 *
	 * @param $playerName
	 * @param $playerId
	 * @param $password
	 * @param null $email
	 * @param null $extra
	 * @return array
	 */
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->current_player_id=$playerId;
		// $this->generateToken(); #generate token

		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no auth token'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->api_merchant_code,
			'currency' => $this->currency,
			'username' => $gameUsername,
			'password' => $password,
			'realname' => $gameUsername,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		// $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$result=['response_result_id'=>$responseResultId, 'exists'=>false];
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		if(@$resultArr['code']==self::CODE_DUPLICATE_USERNAME){
			//if duplicate means success
			$success=true;
			$result['exists']=true;
		}
		if($success){
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array($success,$result);
	}

	/**
	 * Check Player if exist
	 *
	 * @param $playerName
	 * @return array
	 */
	public function isPlayerExist($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->current_player_id=$this->getPlayerIdFromUsername($playerName);
		// $this->generateToken(); #generate token
		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no auth token'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'gameUsername' => $gameUsername,
			'playerId'=>$this->current_player_id,
			'playerName' => $playerName,
		);

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->api_merchant_code,
			'currency' => $this->currency,
			'game_platform_id' => $this->getOriginalPlatformCode(),
			'username' => $gameUsername,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		// $playerId = $this->getPlayerIdInPlayer($playerName);
		$result=[];
		if($success){
			if(array_key_exists('detail', $resultArr) &&
					array_key_exists('existed', $resultArr['detail'])){
				$result = array('exists' => $resultArr['detail']['existed']);
				if ($resultArr['detail']['existed']) {
					$playerId = $this->getVariableFromContext($params, 'playerId');
					# update flag to registered = true
					$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
				}
			}
	    }else{
            $success = true;
            $result['exists'] = false;
        }

		return array($success, $result);
	}

	public function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->current_player_id=$this->getPlayerIdFromUsername($playerName);
		// $this->generateToken(); #generate token
		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no auth token'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'gameUsername' => $gameUsername,
			'playerId'=>$this->current_player_id,
			'playerName' => $playerName,
		);

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->api_merchant_code,
			'currency' => $this->currency,
			'game_platform_id' => $this->getOriginalPlatformCode(),
			'username' => $gameUsername,
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);
	}

	public function processResultForBlockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$result = array();

		if($success){
			$result['blocked'] = $resultArr['detail']['blocked'];
	        $this->blockUsernameInDB($gameUsername);
		}

		return array($success, $result);
	}

	public function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->current_player_id=$this->getPlayerIdFromUsername($playerName);
		// $this->generateToken(); #generate token
		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no auth token'];
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'gameUsername' => $gameUsername,
			'playerId'=>$this->current_player_id,
			'playerName' => $playerName,
		);

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->api_merchant_code,
			'currency' => $this->currency,
			'game_platform_id' => $this->getOriginalPlatformCode(),
			'username' => $gameUsername
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
	}

	public function processResultForUnblockPlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$result = array();

		if($success){
			$result['blocked'] = $resultArr['detail']['blocked'];
	        $this->unblockUsernameInDB($gameUsername);
		}

		return array($success, $result);
	}

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

	/**
	 *
	 * @param array $extra : game_unique_id or game_type or both null, platform, language, redirection
	 */
	public function queryForwardGame($playerName, $extra){
		// $this->generateToken(); #generate token

		$this->current_player_id=$this->getPlayerIdFromUsername($playerName);
		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no auth token'];
		}
		$this->utils->debug_log('queryForwardGame extra', $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'playerId'=>$this->current_player_id,
		);

		$player_token = $this->getPlayerToken($this->current_player_id);
		$next = $this->CI->utils->getSystemUrl('player','/iframe_module/iframe_viewMiniCashier/'. $this->getPlatformCode() );
		$mini_cashier_url = $this->CI->utils->getSystemUrl('player','/iframe/auth/login_with_token/' . $player_token ."?next=".$next);
		# add return url lobby / mobile
		$lobby_url = $this->CI->utils->getSystemUrl('www');
		if(!array_key_exists('platform', $extra)){
			$extra['platform']='pc';
		}
		if($extra['platform']=="mobile"){
			$lobby_url = $this->CI->utils->getSystemUrl('m') . $this->mobile_lobby_path;
		}
		if(!array_key_exists('mode', $extra)){
			$extra['mode']='real';
		}
		// convert $extra['mode'] from fun/demo to trial
		if(in_array($extra['mode'], ['fun', 'demo'])){
			// $this->generateToken(); #generate token
			$extra['mode']='trial';
			// unset($this->home_link);
		}

		$on_error_redirect = (!empty($this->on_error_redirect)?$this->on_error_redirect:$lobby_url);
		$this->home_link = (!empty($this->home_link)?$this->home_link:$lobby_url);
		$this->cashier_link = (!empty($this->cashier_link)?$this->cashier_link:$lobby_url);

		if(isset($extra['home_link'])){ #if set , force to get from param
			$this->home_link = $extra['home_link'];
		}

		if(isset($extra['extra']['home_link'])){ #if set , force to get from param
			$this->home_link = $extra['extra']['home_link'];
		}

		if(isset($extra['extra']['cashier_link'])){ #if set , force to get from param
			$this->cashier_link = $extra['extra']['cashier_link'];
		}

		if($this->home_link == "dynamic_home_url_for_iframe") {
			$this->home_link = "https://" . $_SERVER['HTTP_HOST'] . "/player_center/goto_home_url?home_url=" . $_SERVER['HTTP_HOST'];
			$this->home_link = str_replace("player.", "", $this->home_link);
		}
		
		if($this->cashier_link == "dynamic_cashier_url_for_iframe") {
			$this->cashier_link = "https://" . $_SERVER['HTTP_HOST'] . "/player_center/goto_home_url?cashier_url=" . $_SERVER['HTTP_HOST'];
			$this->cashier_link = str_replace("player.", "", $this->cashier_link);
		}

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->api_merchant_code,
			'game_platform_id' => $this->getOriginalPlatformCode(),
			'currency' => $this->currency,
			'username' => $gameUsername,
			'is_mobile' => $extra['platform'] == 'mobile' ? true : false,
			'game_mode'=>$extra['mode'],
			'extra_settings' => array(
				'currency' => $this->game_launcher_currency,
				'redirection'=>isset($extra['redirection']) ? $extra['redirection'] : null,
				'try_get_real_url'=>$this->try_get_real_url, //it's safe to get real url , instead of launch_game_with_token
				't1_mobile_logout_url' => $this->utils->getSystemUrl('m') . $this->mobile_logout_path,
				't1_lobby_url' => $lobby_url,
				't1_mini_cashier_url' => $mini_cashier_url,
				't1_support_url' => $this->support_url,
				't1_merchant_code' => $this->api_merchant_code,
				'on_error_redirect' => $on_error_redirect,
				'home_link' => $this->home_link,
				'cashier_link' => $this->cashier_link,
				'disable_home_link' => $this->disable_home_link,
			),
		);

		$extraLanguage = '';
        
        if( isset($extra['language']) && !empty($extra['language']) ){
            $extraLanguage = $extra['language'];
        }

		$params['language'] = $this->getLanguagePrecedence( $extraLanguage, $this->language );

		// if(!empty($extra['language'])){
		// 	$params['language']=$extra['language'];
		// }
		if(!empty($extra['game_unique_id'])){
			$params['game_unique_id']=$extra['game_unique_id'];
		}else{
			if(!empty($extra['game_code'])){
				$params['game_unique_id']=$extra['game_code'];
			}
		}
		if(!empty($extra['game_type'])){
			$params['game_type']=$extra['game_type'];
		}
		if(!empty($extra['game_mode'])){
			$params['game_mode']=$extra['game_mode'];
		}

		$this->method = self::METHOD_POST;

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = array();
        $result['url'] = '';
        if($success){
            if (!empty($resultArr['detail']['launcher']['url'])) {
                $result['url'] =  $resultArr['detail']['launcher']['url'];
            }

            if (!empty($resultArr['detail']['launcher']['launchGameExtra']['params'])) {
                $result['params'] =  $resultArr['detail']['launcher']['launchGameExtra']['params'];
            }
       	} else {
       		if(isset($resultArr['code'])){
       			$result['error_message'] = lang('API REQUEST FAILED: Unknown');
       			$error_code = $resultArr['code'];
       			$result['error_message'] = $this->get_error_message($error_code);
       		}
       	}

        return array($success, $result);
	}

	private function get_error_message($error_code){
		switch ($error_code) {
			case self::CODE_INVALID_REQUEST:
				return lang('INVALID REQUEST');
				break;
		    case self::CODE_LOCK_FAILED:
		    	return lang('LOCK FAILED');
		    	break;
		    case self::CODE_INTERNAL_ERROR:
		    	return lang('INTERNAL ERROR');
		    	break;
		    case self::CODE_AGENT_IS_SUSPENDED:
		    	return lang('AGENT IS SUSPENDED');
		    	break;
		    case self::CODE_AGENT_IS_FROZEN:
		    	return lang('AGENT IS FROZEN');
		    	break;
		    case self::CODE_AGENT_IS_INACTIVE:
		    	return lang('AGENT IS INACTIVE');
		    	break;
		    case self::CODE_INVALID_SECURE_KEY:
		    	return lang('INVALID SECURE KEY');
		    	break;
		    case self::CODE_INVALID_USERNAME:
		    	return lang('INVALID USERNAME');
		    	break;
		    case self::CODE_USERNAME_DOES_NOT_BELONG_MERCHANT:
		    	return lang('USERNAME DOES NOT BELONG MERCHANT');
		    	break;
		    case self::CODE_INVALID_PASSWORD:
		    	return lang('INVALID PASSWORD');
		    	break;
		    case self::CODE_DUPLICATE_USERNAME:
		    	return lang('DUPLICATE USERNAME');
		    	break;
			case self::CODE_INVALID_GAME_MODE:
				return lang('INVALID GAME MODE');
				break;
			case self::CODE_LOAD_GAME_API_FAILED:
				return lang('LOAD GAME API FAILED');
				break;
			case self::CODE_GAME_PLATFORM_ON_MAINTENANCE:
				return lang('GAME PLATFORM ON MAINTENANCE');
				break;
			case self::CODE_GAME_PLATFORM_IS_DISABLED:
				return lang('GAME PLATFORM IS DISABLED');
				break;
			case self::CODE_INVALID_GAME_PLATFORM_ID:
				return lang('INVALID GAME PLATFORM ID');
				break;
			case self::CODE_NO_ACTIVE_GAME_PLATFORM_IN_AGENT:
				return lang('NO ACTIVE GAME PLATFORM IN AGENT');
				break;
			case self::CODE_NO_PERMISSION_ON_GAME_PLATFORM:
				return lang('NO PERMISSION ON GAME PLATFORM');
				break;
			case self::CODE_NOT_FOUND_GAME_UNIQUE_ID:
				return lang('NOT FOUND GAME UNIQUE ID');
				break;
		    case self::CODE_PLAYER_IS_BLOCKED_IN_GAME:
		    	return lang('PLAYER IS BLOCKED IN GAME');
		    	break;
			case self::CODE_EXTERNAL_API_ERROR:
				return lang('EXTERNAL API ERROR');
				break;
			case self::CODE_INVALID_FROM_DATE_TIME:
				return lang('INVALID FROM DATE TIME');
				break;
			case self::CODE_INVALID_TO_DATE_TIME:
				return lang('INVALID TO DATE TIME');
				break;
			case self::CODE_INVALID_SIZE_PRE_PAGE:
				return lang('INVALID SIZE PRE PAGE');
				break;
			case self::CODE_INVALID_PAGE_NUMBER:
				return lang('INVALID PAGE NUMBER');
				break;
			case self::CODE_INVALID_GAME_HISTORY_STATUS:
				return lang('INVALID GAME HISTORY STATUS');
				break;
			case self::CODE_INVALID_DATE_MODE:
				return lang('INVALID DATE MODE');
				break;
			case self::CODE_INVALID_MULTIPLE_GAME_PLATFORM:
				return lang('INVALID MULTIPLE GAME PLATFORM');
				break;
			case self::CODE_INVALID_BET_DETAIL_ID:
				return lang('INVALID BET DETAIL ID');
				break;
			case self::CODE_INVALID_MERCHANT_CODE:
				return lang('INVALID MERCHANT CODE');
				break;
			case self::CODE_INVALID_CATEGORY_MODE:
				return lang('INVALID CATEGORY MODE');
				break;
			case self::CODE_INVALID_IS_MOBILE:
				return lang('INVALID IS MOBILE');
				break;
			case self::CODE_NOT_FOUND_REMOTE_WALLET_UNIQUEID:
				return lang('NOT FOUND REMOTE WALLET UNIQUEID');
				break;
			case self::CODE_UNIQUEID_IS_REQUIRED:
				return lang('UNIQUEID IS REQUIRED');
				break;
			case self::CODE_USERNAME_NOT_EXIST:
				return lang('USERNAME NOT EXIST');
				break;
			case self::CODE_INVALID_PLAYER_PREFIX:
				return lang('INVALID PLAYER PREFIX');
				break;
			case self::CODE_INVALID_FREE_ROUND_EXPIRATION_DATE:
				return lang('INVALID FREE ROUND EXPIRATION DATE');
				break;
			case self::CODE_GAME_PLATFORM_DOESNT_SUPPORT_FREE_ROUND_BONUS:
				return lang('GAME PLATFORM DOESNT SUPPORT FREE ROUND BONUS');
				break;
			case self::CODE_INVALID_FREE_ROUND_TRANSACTION_ID:
				return lang('INVALID FREE ROUND TRANSACTION ID');
				break;
			case self::CODE_GAME_PLATFORM_DOES_NOT_SUPPORT_BET_DETAIL_LINK:
				return lang('GAME PLATFORM DOES NOT SUPPORT BET DETAIL LINK');
				break;
			default:
				return lang('API REQUEST FAILED: Unknown');
				break;
		}
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra){
		return $this->returnUnimplemented();
	}

	public function syncOriginalGameLogs($token) {
		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		if($this->enabled_t1_merging_rows){
			$enabled_game_logs_unsettle = true;
			return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
		}
		return $this->returnUnimplemented();
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		$start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            $results = [];
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName = "t1games_seamless_game_logs_{$yearMonthStr}";
                $this->CI->load->model('original_game_logs_model');

                $sqlTime="t1_sg.updated_at >= ? AND t1_sg.updated_at <= ? AND t1_sg.game_platform_id = ?";
				if($use_bet_time){
					$sqlTime="t1_sg.bet_time >= ? AND t1_sg.bet_time <= ? AND t1_sg.game_platform_id = ?";
				}
        $sql = <<<EOD
SELECT
DISTINCT(t1_sg.round_number),
t1_sg.player_username
FROM {$tableName} as t1_sg
WHERE
{$sqlTime}
EOD;
                $params=[
                    $dateFrom,
                    $dateTo,
                    $this->getOriginalPlatformCode()
                ];
                $this->CI->utils->debug_log('query distinct bet transacstion t1_sg merge sql', $sql, $params);

                $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                $results = array_merge($results, $monthlyResults);
            }

            if(!empty($results)){
                $results = array_unique($results, SORT_REGULAR);
                $this->preProcessResults($results, $dateFrom, $dateTo);
                $this->batchProcessBeforeSplitRows($results, null);
            }

            return $results;
	}

	private function preProcessResults( array &$results, $dateFrom, $dateTo){
        if(!empty($results)){
            foreach ($results as $key => $result) {
				$roundNumber = $result['round_number'];
				$playerUsername = $result['player_username'];
				$rows = $this->queryOglByRoundNumber($playerUsername, $roundNumber, $dateFrom, $dateTo);
				$lastRow = end($rows);
				$realBetAmount = 0;
				$effectiveBetAmount = 0;
				$resultAmount = 0;
				$payoutAmount = 0;
				$betDetails = [];
				array_walk($rows, function($data, $key) use(&$realBetAmount, &$effectiveBetAmount, &$resultAmount, &$payoutAmount, &$betDetails) {
                    $realBetAmount += $data['real_bet_amount'];
                    $effectiveBetAmount += $data['effective_bet_amount'];
                    $resultAmount += $data['result_amount'];
                    $payoutAmount += $data['payout_amount'];
                    $betDetails[] = json_decode($data['bet_details']);
                });

                $gameData = $rows[0];
                $gameData['real_bet_amount'] = $realBetAmount;
                $gameData['effective_bet_amount'] = $effectiveBetAmount;
                $gameData['result_amount'] = $resultAmount;
                $gameData['payout_amount'] = $payoutAmount;
                $gameData['after_balance'] = $lastRow['after_balance'];
                $gameData['game_details'] = $lastRow['game_details'];
                $gameData['payout_time'] = $lastRow['payout_time'];
                $gameData['bet_details'] = $betDetails;
                $gameData['updated_at'] = $lastRow['updated_at'];
                $gameData['update_version'] = $lastRow['update_version'];
                $gameData['detail_status'] = $lastRow['detail_status'];
                $gameData['t1_game_platform_id'] = $this->getPlatformCode();
                $gameData['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($gameData, ['updated_at', 'payout_time', 'update_version', 'detail_status'], ['real_bet_amount', 'effective_bet_amount', 'result_amount', 'payout_amount']);
                $results[$key] = $gameData;
            }
        }
    }

    private function queryOglByRoundNumber($playerUsername, $roundNumber, $dateFrom, $dateTo){
        $start = (new DateTime($dateFrom))->modify('first day of this month');
        $end = (new DateTime($dateTo))->modify('last day of this month');
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);
        $results = [];
        foreach ($period as $dt) {
            $yearMonthStr =  $dt->format("Ym");
            $tableName = "t1games_seamless_game_logs_{$yearMonthStr}";
            $this->CI->load->model('original_game_logs_model');
    $sql = <<<EOD
SELECT
t1_sg.*,
gd.id as game_description_id,
gd.game_type_id,
gpu.player_id

FROM {$tableName} as t1_sg
LEFT JOIN game_description as gd ON t1_sg.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_provider_auth as gpu ON t1_sg.player_username = gpu.login_name and gpu.game_provider_id = ?
WHERE
t1_sg.player_username = ? and t1_sg.round_number = ? and t1_sg.game_platform_id = ?
EOD;

            $params=[
				$this->getPlatformCode(),
				$this->getPlatformCode(),
                $playerUsername,
                $roundNumber,
                $this->getOriginalPlatformCode()
            ];

			$this->CI->utils->debug_log('queryOglByRoundNumber', $sql, $params);

            $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            $results = array_merge($results, $monthlyResults);
        }
        return array_values($results);
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

	// use parent queryPlayerBalance
	// public function queryPlayerBalance($playerName) {

	protected $multiplePlatformIdMap;
	protected $unknownGameTypeMap;
	protected $always_manually_sync_t1_gamegateway;

	const GAME_HISTORY_STATUS_NORMAL='normal';
	const GAME_HISTORY_STATUS_OTHERS='others';

	const METHOD_POST = 'POST';
    const ESPORTS_GAME = ["esports", "e_sports"];

	const MD5_FIELDS_FOR_API=[
        'external_uniqueid', 'effective_bet_amount', 'result_amount', 'real_bet_amount', 'payout_time',
        'username', 'player_username', 'bet_time', 'game_platform_id', 'game_code', 'updated_at', 'detail_status', 'rent'];

    const MD5_FLOAT_AMOUNT_FIELDS=[
    	'effective_bet_amount', 'result_amount', 'real_bet_amount', 'rent',
    ];

    /**
     * batchProcessBeforeSplitRows
     * @param  array &$rows
     * @param  int $responseResultId
     */
    public function batchProcessBeforeSplitRows(array &$rows, $responseResultId){
    	$this->CI->load->model(['game_logs']);
        $cntOfRows=count($rows);
        for($i=0; $i<$cntOfRows; $i++) {
	        //generate md5 sum if empty
	        if(empty($rows[$i]['md5_sum'])){
	        	//any model, it's on base model
	        	$rows[$i]['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($rows[$i], self::MD5_FIELDS_FOR_API, self::MD5_FLOAT_AMOUNT_FIELDS);
	        }

	        if(is_null($rows[$i]['effective_bet_amount'])){
	        	$rows[$i]['effective_bet_amount']=0;
	        }
	        if(is_null($rows[$i]['real_bet_amount'])){
	        	$rows[$i]['real_bet_amount']=$rows[$i]['effective_bet_amount'];
	        }
	        if(is_null($rows[$i]['result_amount'])){
	        	$rows[$i]['result_amount']=0;
	        }

	        //detail_status to status and response_result_id
        	//same status
        	$rows[$i]['status']=$rows[$i]['detail_status'];
	        $rows[$i]['response_result_id']=$responseResultId;
        }
    }

    /**
     * sync directly to game logs
     *
     * @param  string $token
     * @return array ['success']
     */
	public function syncDirectlyAllT1GameLogs($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $adjust_datetime_str = $this->getValueFromSyncInfo($token, 'adjust_datetime_str');
        //observer the date format
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
         # adjust range for GG poker
        if($this->getOriginalPlatformCode() == GGPOKER_GAME_API){
	        $startDate = $startDate->format('Y-m-d 00:00:00');
			$endDate = $endDate->format('Y-m-d 23:59:59');
		}else{
			if(empty($adjust_datetime_str)){
				$adjust_datetime_str=$this->getDatetimeAdjust();
			}
			$startDate->modify($adjust_datetime_str);
        	$startDate= $this->CI->utils->formatDateTimeForMysql($startDate);
        	$endDate= $this->CI->utils->formatDateTimeForMysql($endDate);
		}
		$this->multiplePlatformIdMap=$this->getValueFromSyncInfo($token, 'multiplePlatformIdMap');
		$this->unknownGameTypeMap=$this->getValueFromSyncInfo($token, 'unknownGameTypeMap');
		//use_bet_time > ignore_public_sync > config
		if($this->existsValueFromSyncInfo($token, 'use_bet_time')){
        	$this->always_manually_sync_t1_gamegateway = $this->getValueFromSyncInfo($token, 'use_bet_time');
		}else if($this->existsValueFromSyncInfo($token, 'ignore_public_sync')){
	        $this->always_manually_sync_t1_gamegateway = $this->getValueFromSyncInfo($token, 'ignore_public_sync');
		}else{
        	//try get config
        	$this->always_manually_sync_t1_gamegateway = $this->CI->utils->getConfig('always_manually_sync_t1_gamegateway');
        }

        if(empty($this->multiplePlatformIdMap)){
	        return array('success'=>false, 'error_message'=>'multiplePlatformIdMap is empty');
        }

        $error_message=null;
        $normalResult=null;
        $otherResult=null;
		//pagination, start from 1
        $page = 1;
		$this->CI->utils->debug_log('---------------try get settled game logs', $this->multiplePlatformIdMap, $startDate, $endDate,
			$adjust_datetime_str, $page, self::GAME_HISTORY_STATUS_NORMAL, $this->always_manually_sync_t1_gamegateway);
		$successOthers=false;
		$successNormal=$this->syncDirectlyAllPaginate($startDate, $endDate, $page, self::GAME_HISTORY_STATUS_NORMAL, $normalResult);
		if(!$successNormal){
			$error_message='sync GAME_HISTORY_STATUS_NORMAL failed';
		}else if($this->enable_sync_t1_unsettle){
			$this->CI->utils->debug_log('---------------try get unsettled game logs', $this->multiplePlatformIdMap, $startDate, $endDate,
				$adjust_datetime_str, $page, self::GAME_HISTORY_STATUS_OTHERS, $this->always_manually_sync_t1_gamegateway);
			$successOthers=$this->syncDirectlyAllPaginate($startDate, $endDate, $page, self::GAME_HISTORY_STATUS_OTHERS, $otherResult);
			if(!$successOthers){
				$error_message='sync GAME_HISTORY_STATUS_OTHERS failed';
			}
		}else{
			//disable unsettle
			$successOthers=true;
		}
		$success= $successNormal && $successOthers;

        return array('success'=>$success, 'error_message'=>$error_message, 'normalResult'=>$normalResult, 'otherResult'=>$otherResult);
	}

	public function syncDirectlyAllPaginate($startDate, $endDate, $page, $query_status, &$result){

		$this->utils->debug_log('start syncPaginate================',$startDate, $endDate, $page, $query_status);

		$result=['total_pages'=>0, 'applied_data_count'=>0, 'api_data_count'=>0];
		$done=false;
		while (!$done) {
		    $rlt = $this->callT1GamelogsApi($startDate, $endDate, $page, $query_status);
		    if($rlt['success']){

		    	$this->utils->info_log('sync game logs api result', $rlt);

		    	if($rlt['total_pages']>$rlt['current_page']){
		    		$page=$rlt['current_page']+1;
		    		$this->utils->debug_log('not done================',$rlt['total_pages'],$rlt['current_page']);
		    	}else{
		    		$done=true;
		    		$this->utils->debug_log('done===================',$rlt['total_pages'],$rlt['current_page']);
		    	}
		    	$result['applied_data_count']+=$rlt['applied_data_count'];
		    	$result['api_data_count']+=$rlt['api_data_count'];
		    	if(isset($rlt['total_pages'])){
		    		$result['total_pages']=$rlt['total_pages'];
		    	}
		    	$success=true;
		    }else{
		    	$success=false;
		    	$done=true;
		    	$this->utils->error_log('sync game logs api error', $rlt);
		    }
        }

        return $success;
	}

    public function callT1GamelogsApi($startDate, $endDate, $page, $query_status){
        // $this->generateToken(false); #generate token

        $context = array(
            'callback_obj' => $this,
            'callback_method' => !$this->enabled_t1_merging_rows ? 'processResultForCallT1GamelogsApi' : 'processResultForCallT1GamelogsApiAsOgl',
            'from' => $startDate,
            'to' => $endDate,
        );

        $use_bet_time=$this->always_manually_sync_t1_gamegateway;

        $date_mode = $use_bet_time ? 'by_bet_time' : 'by_last_update_time';

		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no api token'];
		}
		//key is original , value is current code
		$platformIdArray=array_keys($this->multiplePlatformIdMap);
        $params = [
            'auth_token' => $api_token,
            'merchant_code' => $this->api_merchant_code,
            'multiple_game_platform'=>$platformIdArray,
            // 'game_platform_id' => $this->getOriginalPlatformCode(),
            'from' => $startDate,
            'to' => $endDate,
            'page_number' => $page,
            'size_per_page' => $this->size_per_page,
            'game_history_status' => $query_status,
            'date_mode' => $date_mode
        ];

        if($this->currency){
			$params['currency'] = strtolower($this->currency);
		}

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_syncMultiplePlatformGameRecords, $params, $context);
    }

    public function processResultForCallT1GamelogsApi($params) {

        $this->CI->load->model(array('game_logs', 'original_game_logs_model'));

        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $from = $this->getVariableFromContext($params, 'from');
        $to = $this->getVariableFromContext($params, 'to');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $dataCount = 0;
        $apiDataCount=0;
        $result = [];

        if($success && isset($resultArr['detail'])){
        	$detail=$resultArr['detail'];
	        $gameRecords = isset($detail['game_history']) ? $detail['game_history'] : null;

	        if(!empty($gameRecords)){
	        	$apiDataCount=count($gameRecords);
	        	// $this->CI->utils->debug_log('t1 gameRecords ==>', $gameRecords);
	        	//check or generate md5
	        	$this->batchProcessBeforeSplitRows($gameRecords, $responseResultId);

	        	//$gameRecords to $insertRows, $updateRows
	        	//will set game_logs_id to updateRows
	            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->generateInsertAndUpdateForGameLogs(
	            	$gameRecords, 'external_uniqueid', $this->getPlatformCode());
	            unset($gameRecords);
	        	// if($this->is_update_original_row){
	        		// $availableRows = $gameRecords;
	        	// }else{
	        	// 	$availableRows = $this->CI->t1games_game_logs->getAvailableRows($gameRecords,$this->getOriginalPlatformCode());
	        	// }

	            //preprocessOriginalRowForGameLogs
	            //then

		        // $cnt = 0;

		        if(!empty($insertRows)){
		        	$this->batchPreprocessOriginalRowForGameLogs($insertRows);
		        	$insertRows = array_values($insertRows);
		        }
		        if(!empty($updateRows)){
		        	$this->batchPreprocessOriginalRowForGameLogs($updateRows);
		        	$updateRows = array_values($updateRows);
		        }

		        // $this->CI->utils->debug_log($insertRows);

		        // $this->CI->utils->debug_log($updateRows);
		        $makeParamsForInsertOrUpdateGameLogsRow=[$this, 'makeParamsForInsertOrUpdateGameLogsRow'];
		        $api=$this;
		        // only for lottery, spoorts or any game which exists status change, it will delete game_logs or game_logs_unsettle record depends status
		        // and insert/update game_logs_unsettle
		        // and update $insertRows and $updateRows
		        // no need on live casino/slots
		        if($this->enabled_game_logs_unsettle && (!empty($insertRows) || !empty($updateRows)) ){
		            $this->CI->game_logs->processUnsettleGameLogs($api, $makeParamsForInsertOrUpdateGameLogsRow,
		                $insertRows, $updateRows);
		        }

		        if(!empty($insertRows)){
		            $success=$this->commonUpdateOrInsertGameLogs($api, $makeParamsForInsertOrUpdateGameLogsRow,
		                'insert', $insertRows, $dataCount);
		        }
		        unset($insertRows);
		        if($success && !empty($updateRows)){
		            $success=$this->commonUpdateOrInsertGameLogs($api, $makeParamsForInsertOrUpdateGameLogsRow,
		                'update', $updateRows, $dataCount);
		        }
		        unset($updateRows);
		        // unset($unknownGame);

	        }
	        $result['total_pages']=isset($detail['total_pages']) ? $detail['total_pages'] : null;
	        $result['current_page']=isset($detail['current_page']) ? $detail['current_page'] : null;
	        $result['total_rows_current_page']=isset($detail['total_rows_current_page']) ? $detail['total_rows_current_page'] : null;
        }
        unset($resultArr);

        $result['applied_data_count'] = $dataCount;
        $result['api_data_count'] = $apiDataCount;

        return array($success, $result);
    }

    public function processResultForCallT1GamelogsApiAsOgl($params) {

        $this->CI->load->model(array('game_logs', 'original_game_logs_model'));

        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $from = $this->getVariableFromContext($params, 'from');
        $to = $this->getVariableFromContext($params, 'to');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $dataCount = 0;
        $apiDataCount=0;
        $result = [];

        if($success && isset($resultArr['detail'])){
        	$detail=$resultArr['detail'];
	        $gameRecords = isset($detail['game_history']) ? $detail['game_history'] : null;
	        if(!empty($gameRecords)){
	        	$apiDataCount=count($gameRecords);
	        	$monthlyRecords = $this->rebuildOglGameRecords($gameRecords);
	        	if(!empty($monthlyRecords)){
	        		unset($gameRecords);
	        		foreach ($monthlyRecords as $yearMonth => $gameRecords) {
	        			$table = $this->getOglMonthlytable($yearMonth);
	        			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal($table, $gameRecords, 'external_uniqueid', 'external_uniqueid', ['updated_at', 'update_version'], 'md5_sum', 'id', ['real_bet_amount', 'payout_amount', 'result_amount']);

		                $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

		                if (!empty($insertRows)) {
		                    $dataCount += $this->updateOrInsertOriginalGameLogs($table, $insertRows, 'insert',
		                    	['responseResultId'=>$responseResultId]);
		                }
		                unset($insertRows);

		                if (!empty($updateRows)) {
		                    $dataCount += $this->updateOrInsertOriginalGameLogs($table, $updateRows, 'update',
		                		['responseResultId'=>$responseResultId]);
		                }
		                unset($updateRows);
	        		}
	        	}
	        }
	        $result['total_pages']=isset($detail['total_pages']) ? $detail['total_pages'] : null;
	        $result['current_page']=isset($detail['current_page']) ? $detail['current_page'] : null;
	        $result['total_rows_current_page']=isset($detail['total_rows_current_page']) ? $detail['total_rows_current_page'] : null;
        }
        unset($resultArr);

        $result['applied_data_count'] = $dataCount;
        $result['api_data_count'] = $apiDataCount;

        return array($success, $result);
    }

    public function rebuildOglGameRecords(array $rows){
    	$groupedByDate = [];

		foreach ($rows as $row) {
		    $date = date('Ym',strtotime($row['bet_time']));
		    
		    if (!isset($groupedByDate[$date])) {
		        $groupedByDate[$date] = [];
		    }
		    
		    $groupedByDate[$date][] = $row;
		}
		return $groupedByDate;
    }

    private function updateOrInsertOriginalGameLogs($table, $data, $queryType)
    {
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function getOglMonthlytable($yearMonth){
        $tableName = "t1games_seamless_game_logs_{$yearMonth}";
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like t1games_seamless_game_logs');

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

    	//overwrite game platform id
        $extra_info=[
        	// 'external_log_id'=>$row['response_result_id'],
        	't1_game_platform_id'=>$row['t1_game_platform_id'],
        	'odds' => $row['odds'],
        	'odds_type' => $row['odds_type']
        ];

		$bet_details = $row['bet_details'];
		//should be string
		if (!empty($bet_details) && is_string($bet_details)){ // && $this->isJsonData($bet_details)) {
			$bet_details =  json_decode($row['bet_details'], true);
		}
		if(empty($row['updated_at'])){
			$row['updated_at']=$row['payout_time'];
		}

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'], 'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'], 'game_type' => null, 'game' => $row['game_name']
            ],
            'player_info' => ['player_id' => $row['player_id'], 'player_username' => $row['username']],
            'amount_info' => [
                'bet_amount' => $row['effective_bet_amount'], 'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['effective_bet_amount'],
                'real_betting_amount' => ($row['real_bet_amount'] === null ? $row['effective_bet_amount'] : $row['real_bet_amount']),
                'win_amount' => null, 'loss_amount' => null, 'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $this->gameTimeToServerTime($row['bet_time']),
                'end_at' => $this->gameTimeToServerTime($row['payout_time']),
                'bet_at' => $this->gameTimeToServerTime($row['bet_time']),
                //'start_at' => date_modify($row['start_at'],"+11 hours"),
                //'end_at' => date_modify($row['end_at'], '+11 hours'),
                //'bet_at' => date_modify($row['bet_at'], '+11 hours'),
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0, 'external_uniqueid' => $row['external_uniqueid'], 'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'], 'response_result_id' => $row['response_result_id'], 'sync_index' => null,
                'bet_type' => $row['bet_type']
            ],
            'bet_details' => $bet_details,
            'extra' => $extra_info,
            //from exists game logs or game_logs_unsettle_id
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    /**
     *
     * batch setup player id and game_description_id and game_type_id
     *
     * @param  array &$rows
     */
    public function batchPreprocessOriginalRowForGameLogs(array &$rows){
    	// $this->CI->utils->debug_log("batchPreprocessOriginalRowForGameLogs  rows ==> ", $rows);
    	// original code to current code
    	// $this->multiplePlatformIdMap
    	// get all username and game_code and game platform id
        $batchGameUsername=[];
        $batchGameCode=[];
        $cntOfRows=count($rows);
        // $this->CI->utils->debug_log("batchPreprocessOriginalRowForGameLogs  multiplePlatformIdMap ==> ", $this->multiplePlatformIdMap);
        for($i=0; $i<$cntOfRows; $i++) {
        	// $row=$rows[$i];
    		//convert original code to t1 code
    		$originalPlatformId=$rows[$i]['game_platform_id'];
    		if(isset($this->multiplePlatformIdMap[$originalPlatformId])){
    			// batch username
    			// batch game_code
    			$t1PlatformId=$this->multiplePlatformIdMap[$originalPlatformId];

				# no need coz it will use the api returned player_username
				/*
				// ...
				if (!empty($this->fixed_prefix_to_be_remove) && isset($this->fixed_prefix_to_be_remove[$t1PlatformId])) {
					$prefixes_to_remove = $this->fixed_prefix_to_be_remove[$t1PlatformId];
					$this->utils->debug_log("fixed_prefix_to_be_remove  ==> ", $t1PlatformId, $prefixes_to_remove);
					foreach ($prefixes_to_remove as $prefix) {
						if (strpos($rows[$i]['player_username'], $prefix) === 0) {
							$rows[$i]['player_username'] = substr($rows[$i]['player_username'], strlen($prefix));
						}
					}
				}
				// ... it will remove currency prefix e.g brlmdbtestt1dev => mdbtestt1dev
				*/

	        	$key=$t1PlatformId.'-'.$rows[$i]['player_username'];
    			$batchGameUsername[]=$key;

	    		//get game key, game code is external_gameid
	        	$gameKey=$t1PlatformId.'-'.$rows[$i]['game_code'];
	        	$batchGameCode[]=$gameKey;
    		}else{
    			//doesn't exist , should remove
        		$this->utils->error_log('wrong game_platform_id and remove it', $rows[$i]['game_platform_id']);
        		unset($rows[$i]);
    		}

    	}

		var_dump($batchGameUsername);

    	// $this->CI->utils->debug_log("batchPreprocessOriginalRowForGameLogs  batchGameUsername ==> ", $batchGameUsername);
    	$usernamePlayerIdMap=null;
    	$gameCodeGameDescIdMap=null;
    	$this->CI->load->model(['game_provider_auth','game_description_model', 'game_logs']);
    	//batchGameUsername is unique
    	if(!empty($batchGameUsername)){
    		//only run one sql
	    	$usernamePlayerIdMap=$this->CI->game_provider_auth->batchGetPlayerIdByGameUsernames($batchGameUsername);
    	}
        //try process unknown game
    	if(!empty($batchGameCode)){
    		$gameCodeGameDescIdMap=$this->CI->game_description_model->batchGetGameDescIdByCode($batchGameCode);
    	}

    	unset($batchGameUsername);
    	unset($batchGameCode);
        $cntOfRows=count($rows);
        for($i=0; $i<$cntOfRows; $i++) {
        	//use lang to process game name
        	if(isset($rows[$i]['game_name'])){
        		$rows[$i]['game_name']=lang($rows[$i]['game_name']);
        	}
        	// $row=$rows[$i];
    		$originalPlatformId=$rows[$i]['game_platform_id'];
    		$t1PlatformId=$this->multiplePlatformIdMap[$originalPlatformId];
        	$key=$t1PlatformId.'-'.$rows[$i]['player_username'];
        	// $this->CI->utils->debug_log("batchPreprocessOriginalRowForGameLogs  key ==> {$key}  usernamePlayerIdMap==> ", $usernamePlayerIdMap);
        	if(isset($usernamePlayerIdMap[$key])) {
        		$rows[$i]['player_id']=$usernamePlayerIdMap[$key];
        		$rows[$i]['t1_game_platform_id']=$t1PlatformId;

				//process game code and unknown game
		        $gameKey=$t1PlatformId.'-'.$rows[$i]['game_code'];
	        	if(isset($gameCodeGameDescIdMap[$gameKey])) {
	        		$rows[$i]['game_description_id']=$gameCodeGameDescIdMap[$gameKey]['game_description_id'];
	        		$rows[$i]['game_type_id']=$gameCodeGameDescIdMap[$gameKey]['game_type_id'];
	        	}else{
	        		if(isset($this->unknownGameTypeMap[$t1PlatformId])){
		        		$unknownGame=$this->unknownGameTypeMap[$t1PlatformId];

		        		//it's unknown game
		        		$gameDescId=$this->CI->game_description_model->processUnknownGame($t1PlatformId,
		        			$unknownGame['game_type_id'], $rows[$i]['game_name'], $rows[$i]['game_code']);
		        		if(empty($gameDescId)){
		        			$gameDescId=$unknownGame['game_description_id'];
		        		}
		        		$rows[$i]['game_description_id']=$gameDescId;
		        		$rows[$i]['game_type_id']=$unknownGame['game_type_id'];
	        		}else{
	        			//error, remove it
		        		$this->utils->error_log('lost unknown game and remove it', $t1PlatformId, $rows[$i]['game_name'], $rows[$i]['game_code'], $originalPlatformId);
		        		//remove it
		        		unset($rows[$i]);
	        		}
	        	}

        	}else{
        		//lost username
        		$this->utils->error_log('lost username and remove it', $rows[$i]['player_username'], $originalPlatformId);
        		//remove it
        		unset($rows[$i]);
        	}

        }

        unset($usernamePlayerIdMap);
        unset($gameCodeGameDescIdMap);

    }

    public function queryBetDetailLink($playerUsername, $betid = null, $extra = null) {
		if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $betid, $extra);
        }
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $api_token=$this->getAvailableApiToken();

        if (empty($api_token)) {
            return ['success'=>false, 'error_message'=>'no auth token'];
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'gameUsername' => $gameUsername,
            'betUid' => $betid,
        );

        $params = array(
            'auth_token' => $api_token,
            'merchant_code' => $this->api_merchant_code,
            'game_platform_id' => $this->getOriginalPlatformCode(),
            'username' => $gameUsername,
            'bet_detail_id' => $betid,
        );

        if ($this->currency) {
            $params['currency'] = strtolower($this->currency);
        }

        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
        $betUid = @$this->getVariableFromContext($params, 'betUid');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array('url'=>'');

		$this->utils->debug_log('processResultForQueryBetDetailLink-TOT', $resultArr);
        if($success){
            $result['url'] = isset($resultArr['detail']['original_reason']['url']) ? $resultArr['detail']['original_reason']['url'] : $resultArr['detail']['url'];

			if(!empty($resultArr['detail']['html'])){
				$result['html'] =  $resultArr['detail']['html'];
			}
        }

        return array($success, $result);
    }

	public function queryRemoteWalletTransaction($uniqueId){

		$api_token=$this->getAvailableApiToken();
		if(empty($api_token)){
			return ['success'=>false, 'error_message'=>'no auth token'];
		}

		$this->utils->debug_log('queryRemoteWalletTransaction uniqueId', $uniqueId);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryRemoteWalletTransaction',
			'uniqueid' => $uniqueId
		);

		$params = array(
			'auth_token' => $api_token,
			'merchant_code' => $this->api_merchant_code,
			'currency' => $this->currency,
			'uniqueid' => $uniqueId,
		);

		$this->method = self::METHOD_POST;

		return $this->callApi(self::API_queryRemoteWalletTransaction, $params, $context);
	}

	public function processResultForQueryRemoteWalletTransaction($params){
		$uniqueId = $this->getVariableFromContext($params,'uniqueid');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        // $result = [
        //     'response_result_id' => $responseResultId,
        //     'external_transaction_id'=>$uniqueId,
        // ];

        // # set reason_id if possible
        // if(isset($resultArr['detail']['status'])){
        //     $result['status'] = $resultArr['detail']['status'];
        // }
        return array($success, $resultArr);
	}

	private function getLanguagePrecedence( $extraLang, $playerDefaultLang) {

        $language = $playerDefaultLang;
        
        if (!empty($extraLang)) {
            $language = $extraLang;
        }
        return $this->getLauncherLanguage($language);
    }

	private function getLauncherLanguage($language){
        $lang='';
        $language = strtolower($language);
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
            case 'en_US':
            case 'en-us':
            case 'en_us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
            case 'zh_CN':
            case 'zh-cn':
            case 'zh_cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
            case 'id_ID':
            case 'id-id':
            case 'id_id':
                $lang = 'id';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vn':
            case 'vi-VN':
            case 'vi_VN':
            case 'vi-vn':
            case 'vi_vn':
                $lang = 'vi';
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'kr':
            case 'ko-KR':
            case 'ko_KR':
            case 'ko-kr':
            case 'ko_kr':
                $lang = 'ko';
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
            case 'hi_IN':
            case 'hi-in':
            case 'hi_in':
                $lang = 'hi';
                    break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-PT':
            case 'pt_PT':
            case 'pt-pt':
            case 'pt_pt':
                $lang = 'pt';
                break;
            case Language_function::INT_LANG_SPANISH:
            case 'es':
            case 'es-ES':
            case 'es_ES':
            case 'es-es':
            case 'es_es':
                $lang = 'es';
                break;
            case Language_function::INT_LANG_JAPANESE:
            case 'ja':
            case 'ja-JP':
            case 'ja-jp':
            case 'ja-JA':
            case 'ja_JA':
            case 'ja-ja':
            case 'ja_ja':
                $lang = 'ja';
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->enabled_t1_merging_rows){
        	return true;
        }
        
		$tableName="t1games_seamless_game_logs_{$yearMonthStr}";
		if (!$this->CI->utils->table_really_exists($tableName)) {
			try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like t1games_seamless_game_logs');

			}catch(Exception $e){
				$this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
			}
		}
		return $tableName;
	}
}
