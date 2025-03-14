<?php

/**
 * Api_common core module: game list and tokens
 * Separated 7/01/2019
 * @see		api_common.php
 */
trait comapi_core_game_list_tokens {

	/**
	 * If true, listGame* series would require username and token.  Defaults to false.
	 * @var boolean
	 */
	protected $lg_login_req = false;

	/**
	 * Lists game platforms configured on system
	 * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses	int		POST:refresh		non-zero or nonblank to force refresh
	 *
	 * @return	JSON
	 */
    public function listGamePlatforms() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	try {
    		$refresh		= !empty($this->input->post('refresh', 1));
    		$request 		= [ 'api_key' => $api_key, 'lg_login_requirement' => $this->lg_login_req ];
    		if ($this->lg_login_req) {
				$token			= trim($this->input->post('token', 1));
	    		$username		= trim($this->input->post('username', 1));
	    		$request = array_merge($request, [ 'username' => $username, 'token' => $token ]);
    		}
    		$this->comapi_log(__METHOD__, 'request', $request);

    		if ($this->lg_login_req) {
	    		$playerId = $this->player_model->getPlayerIdByUsername($username);
	    		if (empty($playerId)) {
	    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
	    		}

	    		// Check player token
	    		if (!$this->__isLoggedIn($playerId, $token)) {
	    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
	    		}
    		}

			// Exclude providers listed in GAME_PROVIDERS_CONCEAL
   			$this->load->model([ 'external_system', 'comapi_settings_cache' ]);

   			$res = $this->comapi_settings_cache->new_real_list_game_platforms();

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> 'Successfully got the list of game platforms' ,
	    		'result'	=> $res
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	} // End of function listGamePlatforms()

	/**
	 * Lists game lobby URL, or game types for platforms without lobby
	 * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    int		POST:platform_id	platform_id as id reported by listGamePlatforms
     * @uses	int		POST:refresh		non-zero or nonblank to force refresh
     * @uses	int		POST:force_lang		force language, overriding SBE set preference.  Defaults to 2 (chinese).
	 *
	 * @return	JSON
	 */
	public function listGamesByPlatform() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	try {
    		$platform_id	= intval($this->input->post('platform_id', 1));
    		$refresh		= !empty($this->input->post('refresh', 1));
    		$force_lang		= intval($this->input->post('force_lang', 1));
    		$request 		= [ 'api_key' => $api_key, 'platform_id' => $platform_id, 'refresh' => $refresh, 'force_lang' => $force_lang, 'lg_login_requirement' => $this->lg_login_req ];
    		if ($this->lg_login_req) {
				$token			= trim($this->input->post('token', 1));
	    		$username		= trim($this->input->post('username', 1));
	    		$request = array_merge($request, [ 'username' => $username, 'token' => $token ]);
    		}
    		$this->comapi_log(__METHOD__, 'request', $request);

    		if ($this->lg_login_req) {
	    		$playerId = $this->player_model->getPlayerIdByUsername($username);
	    		if (empty($playerId)) {
	    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
	    		}

	    		// Check player token
	    		if (!$this->__isLoggedIn($playerId, $token)) {
	    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
	    		}
	    	}

    		if (empty($platform_id)) {
    			throw new Exception("Platform ID invalid", self::CODE_LG_PLATFORM_ID_INVALID);
    		}

    		$force_lang = $force_lang ?: null;

    		if ($force_lang > 6 || $force_lang < 1) {
    			$force_lang = null;
    		}

    		$this->load->model('comapi_settings_cache');
    		$gp_conceal = $this->comapi_settings_cache->settings_get_list_game('GAME_PROVIDERS_CONCEAL');
    		if (isset($gp_conceal[$platform_id])) {
    			throw new Exception("Platform ID '$platform_id' not supported", self::CODE_LG_PLATFORM_ID_NOT_SUPPORTED);
    		}

			$this->load->model('comapi_settings_cache');

			$res = $this->comapi_settings_cache->new_fgapi_list_games_by_platform($platform_id, $refresh, $force_lang);

			if ($res['success'] != true) {
				throw new Exception($res['mesg'], $res['code']);
			}

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> isset($res['mesg']) ? $res['mesg'] : 'Successfully got the list of game types' ,
	    		'result'	=> $res['result']
	    	];

	    	$this->comapi_log(__METHOD__, 'Response', $ret);
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {

	    	$this->returnApiResponseByArray($ret);
	    }
	} // End of function listGameTypesByPlatform()

	/**
	 * Lists game details and launch URL by platform and game type
	 * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    int		POST:platform_id	platform_id as id reported by listGamePlatforms
     * @uses    string	POST:game_type		game_type as game_types reported by listGamesByPlatform
     * @uses	int		POST:limit			count limit of result rows
     * @uses	int		POST:offset			offset of result
     * @uses	int		POST:refresh		non-zero or nonblank to force refresh
	 *
	 * @return	JSON
	 */
	public function listGamesByPlatformGameType() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	$debug = false;

    	try {
    		$platform_id	= intval($this->input->post('platform_id', 1));
    		$game_type		= trim($this->input->post('game_type', 1));

			$order_by		= trim($this->input->post('order_by', 1));
    		$order_by_direction		= trim($this->input->post('order_by_direction', 1));

			if (!$this->input->post('order_by_direction')) {
				$order_by_direction		= 'asc';
			}

			if (!$this->input->post('order_by') && $this->CI->utils->getConfig('set_game_list_default_order_by_to_game_order')) {
				$order_by				= 'game_order';

				$order_by_direction		= $this->CI->utils->getConfig('set_game_list_default_order_by_to_game_order_direction');
			}

    		$refresh		= !empty($this->input->post('refresh', 1));
    		$limit			= intval($this->input->post('limit', 1));
    		$offset			= intval($this->input->post('offset', 1));
			$search_key 	= !empty($this->input->post('search_key')) ? $this->input->post('search_key') : null;
    		$request 		= [ 'api_key' => $api_key, 'platform_id' => $platform_id, 'game_type' => $game_type, 'refresh' => $refresh, 'limit' => $limit, 'offset' => $offset, 'lg_login_requirement' => $this->lg_login_req ];
    		if ($this->lg_login_req) {
				$token			= trim($this->input->post('token', 1));
	    		$username		= trim($this->input->post('username', 1));
	    		$request = array_merge($request, [ 'username' => $username, 'token' => $token ]);
    		}
			$this->comapi_log(__METHOD__, 'request', $request);

			if ($this->lg_login_req) {
	    		$playerId = $this->player_model->getPlayerIdByUsername($username);
	    		if (empty($playerId)) {
	    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
	    		}

	   			// Check player token
	    		if (!$this->__isLoggedIn($playerId, $token)) {
	    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
	    		}
	    	}

    		if (empty($platform_id)) {
    			throw new Exception("Platform ID invalid", self::CODE_LG_PLATFORM_ID_INVALID);
    		}

    		$this->load->model('comapi_settings_cache');
    		$gp_conceal = $this->comapi_settings_cache->settings_get_list_game(Comapi_settings_cache::SETTINGS_GAME_PROVIDERS_CONCEAL);
    		if (isset($gp_conceal[$platform_id])) {
    			throw new Exception("Platform ID '$platform_id' not supported", self::CODE_LG_PLATFORM_ID_NOT_SUPPORTED);
    		}

    		if (empty($limit)) { 
				$limit = ($this->utils->getConfig('game_list_limit_player_center_api')) ? $this->utils->getConfig('game_list_limit_player_center_api') : 50; 
			}

    		if (empty($offset)) { $offset = 0; }
            if(empty($order_by)){
                $order_by = 'game_order';
            }
            if(empty($order_by_direction)){
                $order_by_direction = 'desc';
            }

    		$cf_res = $this->comapi_settings_cache->new_fgapi_list_games_by_platform_gametype($platform_id, $game_type, $refresh, $limit, $offset, $search_key, $order_by, $order_by_direction);

			if ($cf_res['success'] != true) {
				throw new Exception($cf_res['mesg'], $cf_res['code']);
			}

			# binds favorite game to the list 
			$token          = $this->input->post('token', true);
            $username       = $this->input->post('username', true);
			$game_list 		= $cf_res['result']['game_list'];
			$favoriteGames  = [];

			if(!empty($username)){
				$player_id  	= $this->player_model->getPlayerIdByUsername($username);
			}

			if(!empty($player_id) && !empty($token) && $this->__isLoggedIn($player_id, $token)){
				$fav_list  = $this->utils->get_favorites($player_id);
				if($fav_list){
					foreach ($fav_list as $value) {
						$gameUniqueID = $value['external_game_id'];
						$platformID   = $value['game_platform_id'];
						if (!isset($favoriteGames[$gameUniqueID])) {
							$favoriteGames[$gameUniqueID] = [];
						}
						$favoriteGames[$gameUniqueID][] = $platformID;
					}
				}
			}
			foreach ($game_list as $key => $value) {
				$gameUniqueID = $value['game_unique_id'];
				$platformID   = $value['platform_id'];
				$isFavorite   = isset($favoriteGames[$gameUniqueID]) && in_array($platformID, $favoriteGames[$gameUniqueID]);
				$cf_res['result']['game_list'][$key]['isFavorite'] = $isFavorite ? true : false;
			}


	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		// 'mesg'		=> "Successfully got game list for platform={$platform_id},type={$game_type}" ,
	    		'mesg'		=> $cf_res['mesg'] ,
	    		'result'	=> $cf_res['result']
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	} // End of function listGameTypesByPlatform()

	/**
	 * Lists game details and launch URL by platform and game type
	 * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    int		POST:platform_id	platform_id as id reported by listGamePlatforms
     * @uses    string	POST:game_type		game_type as game_types reported by listGamesByPlatform
     * @uses	int		POST:limit			count limit of result rows
     * @uses	int		POST:offset			offset of result
     * @uses	int		POST:refresh		non-zero or nonblank to force refresh
	 *
	 * @return	JSON
	 */
	public function listGamesByPlatformGameTypeTagCode() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	$debug = false;

    	try {
    		$platform_id	= intval($this->input->post('platform_id', 1));
    		$game_type		= trim($this->input->post('game_type', 1));
			$tag_code		= trim($this->input->post('tag_code', 1));
			$order_by		= trim($this->input->post('order_by', 1));
    		$order_by_direction		= trim($this->input->post('order_by_direction', 1));
    		$refresh		= !empty($this->input->post('refresh', 1));
    		$limit			= intval($this->input->post('limit', 1));
    		$offset			= intval($this->input->post('offset', 1));
			$search_key 	= !empty($this->input->post('search_key')) ? $this->input->post('search_key') : null;
    		$request 		= [ 'api_key' => $api_key, 'platform_id' => $platform_id, 'game_type' => $game_type, 'refresh' => $refresh, 'limit' => $limit, 'offset' => $offset, 'lg_login_requirement' => $this->lg_login_req ];
    		if ($this->lg_login_req) {
				$token			= trim($this->input->post('token', 1));
	    		$username		= trim($this->input->post('username', 1));
	    		$request = array_merge($request, [ 'username' => $username, 'token' => $token ]);
    		}
			$this->comapi_log(__METHOD__, 'request', $request);

			if ($this->lg_login_req) {
	    		$playerId = $this->player_model->getPlayerIdByUsername($username);
	    		if (empty($playerId)) {
	    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
	    		}

	   			// Check player token
	    		if (!$this->__isLoggedIn($playerId, $token)) {
	    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
	    		}
	    	}

    		if (empty($platform_id)) {
    			throw new Exception("Platform ID invalid", self::CODE_LG_PLATFORM_ID_INVALID);
    		}

			if (empty($tag_code)){
				throw new Exception("Tag code invalid", self::CODE_LG_TAG_CODE_INVALID);
			}

    		$this->load->model('comapi_settings_cache');
    		$gp_conceal = $this->comapi_settings_cache->settings_get_list_game(Comapi_settings_cache::SETTINGS_GAME_PROVIDERS_CONCEAL);
    		if (isset($gp_conceal[$platform_id])) {
    			throw new Exception("Platform ID '$platform_id' not supported", self::CODE_LG_PLATFORM_ID_NOT_SUPPORTED);
    		}

    		if (empty($limit)) { 
				$limit = ($this->utils->getConfig('game_list_limit_player_center_api')) ? $this->utils->getConfig('game_list_limit_player_center_api') : 50; 
			}

    		if (empty($offset)) { $offset = 0; }

    		$cf_res = $this->comapi_settings_cache->new_fgapi_list_games_by_platform_gametype_tagcode($platform_id, $game_type, $tag_code, $refresh, $limit, $offset, $search_key, $order_by, $order_by_direction);

			if ($cf_res['success'] != true) {
				throw new Exception($cf_res['mesg'], $cf_res['code']);
			}

			# binds favorite game to the list 
			$token          = $this->input->post('token', true);
            $username       = $this->input->post('username', true);
			$game_list 		= $cf_res['result']['game_list'];
			$favoriteGames  = [];

			if(!empty($username)){
				$player_id  	= $this->player_model->getPlayerIdByUsername($username);
			}

			if(!empty($player_id) && !empty($token) && $this->__isLoggedIn($player_id, $token)){
				$fav_list  = $this->utils->get_favorites($player_id);
				if($fav_list){
					foreach ($fav_list as $value) {
						$gameUniqueID = $value['external_game_id'];
						$platformID   = $value['game_platform_id'];
						if (!isset($favoriteGames[$gameUniqueID])) {
							$favoriteGames[$gameUniqueID] = [];
						}
						$favoriteGames[$gameUniqueID][] = $platformID;
					}
				}
			}
			foreach ($game_list as $key => $value) {
				$gameUniqueID = $value['game_unique_id'];
				$platformID   = $value['platform_id'];
				$isFavorite   = isset($favoriteGames[$gameUniqueID]) && in_array($platformID, $favoriteGames[$gameUniqueID]);
				$cf_res['result']['game_list'][$key]['isFavorite'] = $isFavorite ? true : false;
			}

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		// 'mesg'		=> "Successfully got game list for platform={$platform_id},type={$game_type}" ,
	    		'mesg'		=> $cf_res['mesg'] ,
	    		'result'	=> $cf_res['result']
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	} // End of function listGameTypesByPlatform()

	/**
	 * (INTERNAL USE ONLY) Show or import settings for listGame_* series API methods
	 * @param	string	$action			The action; URL arg.  'review' to show current settings, 'import' to import settings from config to db table.  Default is 'review'.
	 * @uses	string	POST:api_key	api_key as security measure.
	 * @return	JSON	General JSON return object, with res =
	 *                  [ to_set, current_set ] 			if action == review;
	 *                  [ before_set, to_Set, after_set] 	if action == import.
	 */
	public function listGame_settings($action = 'review') {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->model('comapi_settings_cache');
			$settings_prefix = Comapi_settings_cache::SETTINGS_PREFIX_LISTGAME;
			$config_key = strtolower("comapi_{$settings_prefix}");

			switch ($action) {

				case 'import' :
					$to_set = $this->utils->getConfig($config_key);
					if (empty($to_set)) {
						$res = null;
						$mesg = "Config item '$config_key' not found, nothing to import";
						break;
					}

					$before_set = $this->comapi_settings_cache->settings_group_get($settings_prefix);

					foreach ($to_set as $settings_key => $value) {
						$settings_key_upper = strtoupper($settings_key);
						$this->comapi_settings_cache->settings_set(
							"{$settings_prefix}:{$settings_key_upper}",
							null,
							is_scalar($value) ? $value : json_encode($value)
						);
					}

					$after_set = $this->comapi_settings_cache->settings_group_get($settings_prefix);

					$res = [ 'before_set' => $before_set, 'to_set' => $to_set, 'after_set' => $after_set ];
					$mesg = 'Imported in-config settings';

					break;

				case 'review' : default :
					$current_set = $this->comapi_settings_cache->settings_group_get($settings_prefix);
					$to_set = $this->utils->getConfig($config_key);

					$res = [ 'current_set' => $current_set , 'to_set' => $to_set ];

					$mesg = 'Reviewing current/in-config settings.';
					if (empty($to_set)) { $mesg .= "  Config item '$config_key' not found."; }
					break;
			}

			$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> $mesg ,
	    		'result'	=> $res
	    	];

		}
		catch (Exception $ex) {
	    	// Catch exception, set in ret and then go to finally block
	    	$ret = [
	    		'success'	=> false ,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> [ $extra_results ]
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	} // End function listGame_settings()

	/**
	 * Lists game category
	 * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses	int		POST:refresh		non-zero or nonblank to force refresh
     * @uses	int		POST:force_lang		force language, overriding SBE set preference.  Defaults to 2 (chinese).
	 *
	 * @return	JSON
	 */
	public function list_game_category() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	try {
    		$refresh		= !empty($this->input->post('refresh', 1));
    		$force_lang		= intval($this->input->post('force_lang', 1));
    		$request 		= [ 'api_key' => $api_key, 'refresh' => $refresh, 'force_lang' => $force_lang, 'lg_login_requirement' => $this->lg_login_req ];
    		if ($this->lg_login_req) {
				$token			= trim($this->input->post('token', 1));
	    		$username		= trim($this->input->post('username', 1));
	    		$request = array_merge($request, [ 'username' => $username, 'token' => $token ]);
    		}

    		$this->comapi_log(__METHOD__, 'request', $request);

    		if ($this->lg_login_req) {
	    		$playerId = $this->player_model->getPlayerIdByUsername($username);
	    		if (empty($playerId)) {
	    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
	    		}

	    		// Check player token
	    		if (!$this->__isLoggedIn($playerId, $token)) {
	    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
	    		}
	    	}

    		$force_lang = $force_lang ?: null;
    		if ($force_lang > 6 || $force_lang < 1) {
    			$force_lang = 1;#default english
    		}


			$this->load->model('comapi_settings_cache');
			$res = $this->comapi_settings_cache->new_fgapi_list_game_category($refresh, $force_lang);

			if ($res['success'] != true) {
				throw new Exception($res['mesg'], $res['code']);
			}

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> isset($res['mesg']) ? $res['mesg'] : 'Successfully got the list of game types' ,
	    		'result'	=> $res['result']
	    	];

	    	$this->comapi_log(__METHOD__, 'Response', $ret);
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {

	    	$this->returnApiResponseByArray($ret);
	    }
	}

	/**
	 * Lists game icon category
	 *
	 * @uses	POST:api_key	string		api_key given by system
	 * @uses	POST:username	string		Player usenname
	 * @uses	POST:token		string		Effective token for player
	 * @uses	POST:force_lang	int	        force language, overriding SBE set preference.  Defaults to 2 (chinese).
	 * @uses	POST:refresh	bool		Use 1 to refresh
	 * @uses	POST:platform_id	int		game platform
	 * @uses	POST:game_type	string		game tag code
	 *
	 * @return	JSON	Standard return JSON structure, with game platforms in result
	 */
	public function list_game_icon_by_category() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	try {
    		$refresh		= !empty($this->input->post('refresh', 1));
    		$force_lang		= intval($this->input->post('force_lang', 1));
    		$request 		= [ 'api_key' => $api_key, 'refresh' => $refresh, 'force_lang' => $force_lang, 'lg_login_requirement' => $this->lg_login_req ];
    		if ($this->lg_login_req) {
				$token			= trim($this->input->post('token', 1));
	    		$username		= trim($this->input->post('username', 1));
	    		$request = array_merge($request, [ 'username' => $username, 'token' => $token ]);
    		}

    		$platform_id	= $this->input->post('platform_id');
    		$game_type		= $this->input->post('game_type');

    		$this->comapi_log(__METHOD__, 'request', $request);

    		if ($this->lg_login_req) {
	    		$playerId = $this->player_model->getPlayerIdByUsername($username);
	    		if (empty($playerId)) {
	    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
	    		}

	    		// Check player token
	    		if (!$this->__isLoggedIn($playerId, $token)) {
	    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
	    		}
	    	}

    		$force_lang = $force_lang ?: null;
    		if ($force_lang > 6 || $force_lang < 1) {
    			$force_lang = 1;#default english
    		}


			$this->load->model('comapi_settings_cache');
			$res = $this->comapi_settings_cache->new_fgapi_list_game_icon_by_category($refresh, $force_lang, $platform_id, $game_type);

			if ($res['success'] != true) {
				throw new Exception($res['mesg'], $res['code']);
			}

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> isset($res['mesg']) ? $res['mesg'] : 'Successfully got the list of game types' ,
	    		'result'	=> $res['result']
	    	];

	    	$this->comapi_log(__METHOD__, 'Response', $ret);
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {

	    	$this->returnApiResponseByArray($ret);
	    }
	}

	/**
	 * Lists game types
	 * @uses    string  POST:api_key        api key given by system
     * @uses	int		POST:refresh		non-zero or nonblank to force refresh
     * @uses	int		POST:force_lang		force language, overriding SBE set preference.  Defaults to 2 (chinese).
     * @uses	int		POST:show_all		show all game types with empty provider.
	 *
	 * @return	JSON
	 */
	public function list_game_types() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	try {
    		$refresh		= intval($this->input->post('refresh', 1));
    		$force_lang		= intval($this->input->post('force_lang', 1));
    		$show_all = intval($this->input->post('show_all', 1));
    		// $platform_id	= intval($this->input->post('platform_id', 1));
    		// $game_type		= trim($this->input->post('game_type', 1));

    		$force_lang = $force_lang ?: null;
    		if ($force_lang > 6 || $force_lang < 1) {
    			$force_lang = 2;#default chinese
    		}

    		$request = [
    			'api_key' => $api_key,
    			'refresh' => $refresh,
    			'force_lang' => $force_lang,
    			'show_all' => $show_all,
    			// 'platform_id' => $platform_id,
    			// 'game_type' => $game_type,
    		];

    		$this->comapi_log(__METHOD__, 'request', $request);

			$this->load->model('comapi_settings_cache');
			$res = $this->comapi_settings_cache->game_type_list_pub($request);

			if ($res['success'] != true) {
				throw new Exception($res['mesg'], $res['code']);
			}

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> isset($res['mesg']) ? $res['mesg'] : 'Successfully got the list of game types' ,
	    		'result'	=> $res['result']
	    	];

	    	$this->comapi_log(__METHOD__, 'Response', $ret);
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	}

	/**
	 * Adds an item to player's favorite games, OGP-23167
	 *
	 * @uses	POST:api_key		string		api_key given by system
	 * @uses	POST:username		string		Player usenname
	 * @uses	POST:token			string		Effective token for player
	 * @uses	POST:platform_id	int	        platform_id reported by listGamesByPlatformGameType
	 * @uses	POST:game_unique_id	string      game_unique_id reported by listGamesByPlatformGameType
	 * @uses	POST:mobile			int	        1 for mobile, otherwise non-mobile
	 * @uses	POST:force_lang		int	        force language.  If 0, uses player center default.
	 * @uses	POST:action			int	        0 for add, 1 for toggle.  Default 0.
	 *
	 * @return	JSON	Standard JSON return structure
	 */
    public function playerGameFavAdd() {
        $api_key = $this->input->post('api_key', 1);
        if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->library([ 'game_list_lib' ]);
			$this->load->model([ 'favorite_game_model' ]);

			$username   	= trim($this->input->post('username', 1));
        	$token   	    = trim($this->input->post('token', 1));
    		$platform_id	= intval($this->input->post('platform_id', 1));
    		$game_unique_id	= trim($this->input->post('game_unique_id', 1));
    		$mobile			= !empty($this->input->post('mobile', 1));
    		$action			= intval($this->input->post('action', 1));
    		$force_lang		= intval($this->input->post('force_lang', 1));

    		$request 		= [ 'api_key' => $api_key, 'username' => $username, 'token' => $username, 'platform_id' => $platform_id, 'game_unique_id' => $game_unique_id, 'mobile' => $mobile, 'action' => $action, 'force_lang' => $force_lang ];
			$this->comapi_log(__METHOD__, 'request', $request);

			// Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            if (empty($platform_id) || empty($game_unique_id)) {
            	throw new Exception(lang('Required argument(s) missing'), self::CODE_COMMON_REQUIRED_ARG_MISSING);
            }

            // Fetch game fron frontend game api by platform_id + game_unique_id
			$gpe_res = $this->game_list_lib->findGameByPlatformAndExtGameId($platform_id, $game_unique_id);

			if ($gpe_res['code'] != 0) {
				$ex_code_mapping = [
					1 => self::CODE_PGV_GAME_PLATFORM_ID_INVALID ,
					2 => self::CODE_PGV_GAME_PLATFORM_HAS_LOBBY ,
				];
				$ex_code = $ex_code_mapping[$gpe_res['code']];

				throw new Exception($gpe_res['mesg'], $ex_code);
			}

			if (count($gpe_res['result']['game_list']) == 0) {
				throw new Exception(lang('No game matches arguments'), self::CODE_PGV_NO_GAME_MATCHES_CRITERIA);
			}

			$game = $gpe_res['result']['game_list'][0];

			// $action: only allow [ 0, 1 ]
			// any value > 0 is considered 1 (toggle), otherwise 0 (add)
			$action = $action > 0 ? 1 : 0;

			$fav_exists = $this->favorite_game_model->exists_by_platform_ext_game_id($player_id, $platform_id, $game_unique_id);

			if (!$fav_exists) {
				// if fav item does not exist: always add
				$pfg_res = $this->comapi_lib->player_favorite_game_add($game, $player_id, $platform_id, $force_lang, $mobile);
			}
			else {
				// if fav item exists
				if ($action == 0) {
					// return exception if action == add
					throw new Exception(lang('Favorite game item already exists'), self::CODE_PGV_REC_ALREADY_EXISTS);
				}
				else {
					// run remove if action == toggle
					$pfg_res = $this->favorite_game_model->remove_by_platform_ext_game_id($player_id, $platform_id, $game_unique_id);
				}
			}

			if (!$pfg_res) {
				throw new Exception(lang('Favorite operation failed, please try later'), self::CODE_PGV_OPERATION_FAILED);
			}

			// Point of successful return

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> 'Favorite game item added/toggled' ,
	    		'result'	=> null
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }

    } // End of function playerGameFavAdd()

    /**
	 * Removes an item to player's favorite games, OGP-23167
	 *
	 * @uses	POST:api_key		string		api_key given by system
	 * @uses	POST:username		string		Player usenname
	 * @uses	POST:token			string		Effective token for player
	 * @uses	POST:fav_id			int	        fav_id reported by playerGameFavList.  Overrides (platform_id, game_unique_id)
	 * @uses	POST:platform_id	int	        platform_id reported by listGamesByPlatformGameType
	 * @uses	POST:game_unique_id	string      game_unique_id reported by listGamesByPlatformGameType
	 *
	 * @return	JSON	Standard JSON return structure
	 */
    public function playerGameFavRemove() {
        $api_key = $this->input->post('api_key', 1);
        if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->library([ 'game_list_lib' ]);
			$this->load->model([ 'favorite_game_model' ]);
			$username   	= trim($this->input->post('username', 1));
        	$token   	    = trim($this->input->post('token', 1));
        	$fav_id			= intval($this->input->post('fav_id', 1));
    		$platform_id	= intval($this->input->post('platform_id', 1));
    		$game_unique_id	= trim($this->input->post('game_unique_id', 1));

    		$request 		= [ 'api_key' => $api_key, 'fav_id' => $fav_id, 'platform_id' => $platform_id, 'game_unique_id' => $game_unique_id ];
			$this->comapi_log(__METHOD__, 'request', $request);

			// Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            if (!empty($fav_id)) {
            	// remove by fav_id
            	$rem_res = $this->favorite_game_model->remove_by_player_id_prim_key($player_id, $fav_id);
            }
            else {
            	// remove by platform_id + game_unique_id
            	if (empty($platform_id) || empty($game_unique_id)) {
            		throw new Exception(lang('Required argument(s) missing'), self::CODE_COMMON_REQUIRED_ARG_MISSING);
            	}
            	$rem_res =  $this->favorite_game_model->remove_by_platform_ext_game_id($player_id, $platform_id, $game_unique_id);
            }

            if (!$rem_res) {
            	throw new Exception(lang('Cannot remove, fav game not found or does not belong to player'), self::CODE_PGV_CANNOT_REMOVE_REC_NOT_FOUND);
            }

            // Point of successful return

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> 'Favorite game item removed' ,
	    		'result'	=> null
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }

    } // End of function playerGameFavRemove()

    /**
	 * Returns player's favorite games, OGP-23167
	 *
	 * @uses	POST:api_key		string		api_key given by system
	 * @uses	POST:username		string		Player usenname
	 * @uses	POST:token			string		Effective token for player
	 *
	 * @return	JSON	Standard JSON return structure
	 */
    public function playerGameFavList() {
        $api_key = $this->input->post('api_key', 1);
        if (!$this->__checkKey($api_key)) { return; }

		try {
			$this->load->library([ 'game_list_lib' ]);

			$username   	= trim($this->input->post('username', 1));
        	$token   	    = trim($this->input->post('token', 1));
    		// $platform_id	= intval($this->input->post('platform_id', 1));

    		$request 		= [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
			$this->comapi_log(__METHOD__, 'request', $request);

			// Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

			$fav_result = $this->utils->get_favorites($player_id);

			if (!empty($fav_result)) {
				foreach ($fav_result as & $row) {
					$row = $this->utils->array_select_fields($row, [ 'id', 'name', 'image', 'url', 'game_platform_id', 'external_game_id', 'created_at' ]);

					$row['fav_id'] = $row['id'];
					$row['game_unique_id'] = $row['external_game_id'];
					$row['platform_id'] = $row['game_platform_id'];
					unset($row['id']);
					unset($row['external_game_id']);
					unset($row['game_platform_id']);

					ksort($row);
				}
			}

			$row_count = count($fav_result);

			$res = [
				'row_count' => $row_count ,
				'rows'		=> $fav_result
			];

			// Point of successful return

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> 'List of player favorite games returned' ,
	    		'result'	=> $res
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->comapi_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);
	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret, 'allow_empty');
	    }

    } // End of function playerGameFavList()

} // End of trait t1t_comapi_module_player_game_log