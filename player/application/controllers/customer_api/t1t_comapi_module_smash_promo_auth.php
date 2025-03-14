<?php

// Cloned from t1t_comapi_module_ole777_reward_sys
trait t1t_comapi_module_smash_promo_auth {

	protected $ttl = 15 * 60;// default

	protected $errors = [
		'SUCCESS'                 => 200 ,
		'ERR_INVALID_SECURE'      => 1010 ,
		// 'ERR_INVALID_MEMBER_CODE' => 1020 ,
		// 'ERR_INVALID_TOKEN'       => 1030 ,
		'ERR_NO_PERMISSION_ACL'   => 1403 ,
		// 'ERR_FAILURE'             => 1900 ,
	];

	public function set_ttl_in_smash_promo(){
		$this->ttl = $this->utils->getConfig('player_center_api_ttl_in_smash_promo');
	}

	/**
	 *  Apply ACL to api called
	 * The rule string of the setting,"api_acl"/"api_acl_override" of Config.
	 *
	 * @param string $method_name The currect method name.
	 * @param string $acl_rule_name The rule name in the setting, api_acl of Config.
	 * @param array $method_params The params of currect method, "$method_name".
	 * @return void
	 */
	public function apply_api_acl($method_name, $acl_rule_name, $method_params = []){
		if (self::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl($method_name, $acl_rule_name)) {
			$this->utils->debug_log("block $acl_rule_name on api $method_name", 'params', $method_params, $this->utils->tryGetRealIPWithoutWhiteIP());
			// return $this->_show_last_check_acl_response('json');
			// return show_error('No permission', 403);
			throw new Exception('No permission by ACL', $this->errors['ERR_NO_PERMISSION_ACL']);
		}
	} // EOF apply_api_acl

	public function _generateValidToken($player_id, $do_public_encrypt = true){
		$this->load->model(array('common_token'));
		$pub_key=$this->utils->getConfig('api_key_player_center_public_key_in_smash_promo_auth');

		$_player_token = $this->common_token->getPlayerToken($player_id);
		if( $do_public_encrypt ){
			$player_token = $this->utils->public_encrypt($_player_token, $pub_key);
		}else{
			$player_token = $_player_token;
		}
		return $player_token;
	}

	public function _isValidToken($input_token, $player_id, $sign){
		$this->load->model(array('common_token'));
		$rlt = $this->common_token->isTokenValid($player_id, (string)$input_token);

		$rltIsValidApiKey = $this->_isValidApiKeyInSmash($sign, $input_token);

		return $rlt && $rltIsValidApiKey;
	}

	public function generateEventUrlWithToken(){
		$std_creds = [];
		try {
			$priv_key=$this->utils->getConfig('api_key_player_center_private_key_in_smash_promo_auth');
			$player_id = $this->input->post('player_id', true);
			$api_key = $this->input->post('api_key', true);
			$reveal_raw_token = $this->input->post('reveal_raw_token', true);
			$do_public_encrypt = true;

			$event_url_in_api = '';
			$token = $this->_generateValidToken($player_id, $do_public_encrypt);
			$event_uri_in_api = $this->utils->getConfig('event_url_list_by_ogp_27441');
			if( ! empty($event_uri_in_api) ){
				$search = ['{TOKEN}'];
				$replace = [$token];
				$event_url_in_api = str_replace($search, $replace, $event_uri_in_api);
			}

			$rlt = [];

			$_raw_token = $this->_generateValidToken($player_id, false);
			if($reveal_raw_token){
				$decrypt_token = $this->utils->private_decrypt($token, $priv_key);
				$isValidDecrypt = $decrypt_token == $_raw_token;
				$rlt = [ 'event_url'=> $event_url_in_api
				, 'is_valid_decrypt' => $isValidDecrypt
				, 'token' => $token
				, 'uid' => $player_id
				, 'sign' => md5($_raw_token. $api_key)
				, 'raw_token' => $_raw_token ];
			}else{
				$rlt = $event_url_in_api;
			}


			$ret = [
				'success'	=> true ,
				'code'		=> $this->errors['SUCCESS'],
				'mesg'		=> 'The event url',
				'result'	=> $rlt
			];

		}
		catch (Exception $ex) {
			$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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
	 * (player_host)/api/smash/useinfo
	 *
	 * @return void
	 */
	public function useinfo(){
		$ret = [];
		$std_creds = [];
		try {
			$this->load->model([ 'common_token', 'player_model' ]);
			$token = $this->input->post('token', true);
			$sign = $this->input->post('sign', true);
			$std_creds['lineNo'] = 149;
			$this->apply_api_acl('useinfo', 'useinfo_of_smash_promo_auth', [$token, $sign]);

			$player_id = $this->common_token->getPlayerIdByToken($token);

			$isValidToken = $this->_isValidToken($token, $player_id, $sign);

			if ( ! $isValidToken ) {
				$std_creds['lineNo'] = 157;
				throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
			}

			// Retrieve player
			$player = $this->player_model->getPlayerById($player_id);

			// Retrieve fields from playerdetails
			$res_details = $this->player_model->getAllPlayerDetailsById($player_id);

			$abridged_fields = [ 'contactNumber' //  phoneNumber（手机号）
								// , 'firstName'
								// , 'lastName'
								// , 'gender'
								// , 'language'
								// , 'birthdate'
								// , 'imAccount'
								// , 'imAccount2'
								// , 'city'
								// , 'address'
								, 'registrationIP' // registerIp（注册IP）、
							];
			$res_details = $this->utils->array_select_fields($res_details, $abridged_fields);

			$playerInfo = $this->player_model->getPlayerInfoById($player_id, null);
			$playerCurrentLevel = $this->player_model->getPlayerCurrentLevel($player_id);
			$currentLevel = !empty($playerCurrentLevel) ? $playerCurrentLevel[0] : NULL;

			$res = $res_details;

			$res['uid'] = $player_id; // uid（用户ID）、
			unset($res['player_id']);

			$res['username'] = $player->username; // username（用户名）、

			$_vip_group = lang($player->groupName);
			$_vip_level = lang($player->levelName);
			// $res['vip_level_display'] = "{$player->groupName} - {$player->levelName}";
			$res['vip_level_display'] = "{$_vip_group} - {$_vip_level}";

			$res['vipLevel'] = $currentLevel['vipLevel'];

			$res['phoneNumber'] = $res['contactNumber'];
			unset($res['contactNumber']);

			$res['email'] = $player->email;  // email（邮箱）、

			$res['registerIp'] = $res['registrationIP']; // registerIp（注册IP）、
			unset($res['registrationIP']);

			$res['verifiedEmail'] = $player->verified_email; // verifiedEmail（邮箱验证状态）：0:未验证，1已验证

			$res['verifiedPhoneNumber'] =  $player->verified_phone; // verifiedEmail（邮箱验证状态）：0:未验证，1已验证

			$res['inviteCode'] = $player->invitationCode;

			ksort($res);

			// If everything goes alright
			$ret = [
				'success'	=> true ,
				'code'		=> $this->errors['SUCCESS'] ,
				'mesg'		=> 'Player profile retrieved successfully',
				'result'	=> $res
			];

		}
	    catch (Exception $ex) {
	    	$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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

	} // EOF useinfo


	private function _mapping_to_game_no($game_description_id){
		$this->load->model(['game_description_model','game_type']);
		$_game_code = null;
		$_game_type_code = null;
		$game_no = null;
		// 游戏类型	编号
		// Crash	1
		// Double	2
		// Dice	3
		// Mines	4
		// Hi-Lo	5
		// Truco	6
		// Slots	7
		// Live Casino	8
		// Sports	9
		$game_no_mapping = $this->utils->getConfig('player_center_api_bet_info_game_no_mapping');

		$_game_description_row = $this->game_description_model->getGameDescById($game_description_id);
		// $_game_description_row = (array)$_game_description_row;
		if( ! empty($_game_description_row) ){
			$_game_code = $_game_description_row['game_code'];
		}
		if( ! empty( $_game_description_row['game_type_code'] ) ){
			$_game_type_code = $_game_description_row['game_type_code'];
		}
		if( ! empty($game_no_mapping[$_game_code]) ){
			$game_no = $game_no_mapping[$_game_code];
		} else if( ! empty($game_no_mapping[$_game_type_code]) ){
			$game_no = $game_no_mapping[$_game_type_code];
		} else{
			// not yet defined.
		}
		return $game_no;
	}

	public function bet_info(){
		$games = $this->utils->getConfig('player_center_api_bet_info_game_list');
		$game_type_list = $this->utils->getConfig('player_center_api_bet_info_game_type_list');
		$std_creds = [];
		$ret = [];
		try {
			$this->load->model([ 'common_token', 'player_latest_game_logs' ]);
			$token = $this->input->post('token', true);
			$sign = $this->input->post('sign', true);
			$reveal_list = $this->input->get_post('reveal_list', true);

			$std_creds['lineNo'] = 258;
			$this->apply_api_acl('bet_info', 'bet_info_of_smash_promo_auth', [$token, $sign]);

			$this->set_ttl_in_smash_promo();
			$res = [];

			$_oneDayAge = new DateTime('1 days ago');
			$use_start_date_today = $this->utils->formatDateForMysql($_oneDayAge);
			$player_center_api_bet_info_use_start_date_today = $this->utils->getConfig('player_center_api_bet_info_use_start_date_today');
			if( ! empty($player_center_api_bet_info_use_start_date_today) ){
				$use_start_date_today = $player_center_api_bet_info_use_start_date_today;
			}

			$player_id = $this->common_token->getPlayerIdByToken($token);
			$isValidToken = $this->_isValidToken($token, $player_id, $sign);

			if ( ! $isValidToken ) {
				$std_creds['lineNo'] = 282;
				throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
			}
			// Retrieve player
			$player = $this->player_model->getPlayerById($player_id);
			$player_username = $player->username;

			$forceRefresh = false;
			$cacheOnly=false; // the ttl, please reference to sync_latest_game_records_cache_ttl in config

			$isCached = false; // reset to default for player_latest_game_logs::get_latest_bets_with_sqls()
			$game_list = $this->player_latest_game_logs->get_latest_bets_with_sqls( $games // #1
				, $isCached // #2
				, $forceRefresh // #3
				, $cacheOnly // #4
				, $player_username // #5
				, $use_start_date_today // #6
				, $this->ttl // #7
			);
			$res['game_list'] = $game_list;
			$res['game_list_isCached'] = $isCached;

			$isCached = false; // reset to default for player_latest_game_logs::get_latest_bets_by_player_and_game_type_with_sqls()
			$game_type_list = $this->player_latest_game_logs->get_latest_bets_by_player_and_game_type_with_sqls( $game_type_list // #1
																												, $player_username // #2
																												, $isCached // #3
																												, $forceRefresh // #4
																												, $cacheOnly // #5
																												, $use_start_date_today // #6
																												, $this->ttl // #7
																											);
			$res['game_type_list'] = $game_type_list;
			$res['game_type_list_isCached'] = $isCached;


			$res['uid'] = $player_id;
			$res['list'] = [];
			foreach($game_list as $_indexNumber => $_row){
				// $_row['betting_datetime']
				// $_row['game_description_id']
				$row = [];
				$row['time'] = $_row['betting_datetime'];
				$row['gameName'] = $this->_mapping_to_game_no($_row['game_description_id']);

				array_push($res['list'], $row);
			}
			foreach($game_type_list as $_indexNumber => $_row){
				// $_row['betting_datetime']
				// $_row['game_description_id']
				$row = [];
				$row['time'] = $_row['betting_datetime'];
				$row['gameName'] = $this->_mapping_to_game_no($_row['game_description_id']);

				array_push($res['list'], $row);
			}

			if( ! $reveal_list){
				/// remove for not required
				unset($res['game_list']);
				unset($res['game_list_isCached']);
				unset($res['game_type_list']);
				unset($res['game_type_list_isCached']);

			}

			$ret = [
				'success'	=> true ,
				'code'		=> $this->errors['SUCCESS'] ,
				'mesg'		=> 'The api, bet_info retrieved successfully',
				'result'	=> $res
			];
		}
	    catch (Exception $ex) {
	    	$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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
	} // EOF bet_info

	public function bet_info_in_latest_time(){



		$games = $this->utils->getConfig('player_center_api_bet_info_game_list');
		$game_type_list = $this->utils->getConfig('player_center_api_bet_info_game_type_list');
		$std_creds = [];
		$ret = [];
		try {
			$this->load->model([ 'common_token', 'player_latest_game_logs' ]);
			$token = $this->input->post('token', true);
			$sign = $this->input->post('sign', true);
			$reveal_list = $this->input->get_post('reveal_list', true);
			$std_creds['lineNo'] = 288;
			$this->apply_api_acl('bet_info_in_latest_time', 'bet_info_of_smash_promo_auth', [$token, $sign]);

			$this->set_ttl_in_smash_promo();
			$res = [];

			$_oneDayAge = new DateTime('1 days ago');
			$use_start_date_today = $this->utils->formatDateForMysql($_oneDayAge);
			$player_center_api_bet_info_use_start_date_today = $this->utils->getConfig('player_center_api_bet_info_use_start_date_today');
			if( ! empty($player_center_api_bet_info_use_start_date_today) ){
				$use_start_date_today = $player_center_api_bet_info_use_start_date_today;
			}

			$player_id = $this->common_token->getPlayerIdByToken($token);
			$isValidToken = $this->_isValidToken($token, $player_id, $sign);

			if ( ! $isValidToken ) {
				$std_creds['lineNo'] = 323;
				throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
			}
			// Retrieve player
			$player = $this->player_model->getPlayerById($player_id);
			$player_username = $player->username;



			$std_creds['player_id'] = $player_id;
			$isCached = false; // Fro collect the result is Cached or Not, default should be false to apply.
			$cacheOnly=false; // the ttl, please reference to sync_latest_game_records_cache_ttl in config
			$forceRefresh = false;
			$use_limit = 20; // #7
			$res['game_list'] = $this->player_latest_game_logs->get_latest_bets( $games // #1
																				, $isCached // #2
																				, $forceRefresh // #3
																				, $cacheOnly // #4
																				, $player_username // #5
																				, $use_start_date_today // #6
																				, $use_limit // #7
																				, $this->ttl // #8
																			);
			$res['game_list_isCached'] = $isCached;

			$use_limit = 10;
			$isCached = false; // reset to default for player_latest_game_logs::get_latest_bets_by_player_and_game_type()
			$res['game_type_list'] = $this->player_latest_game_logs->get_latest_bets_by_player_and_game_type( $game_type_list // #1
																											, $player_username // #2
																											, $isCached // #3
																											, $forceRefresh // #4
																											, $cacheOnly // #5
																											, $use_start_date_today // #6
																											, $use_limit // #7
																											, $this->ttl // #8
																										);
			$res['game_type_list_isCached'] = $isCached;

			$res['uid'] = $player_id; // uid（用户ID）、

			$res['time'] = null; //  9个中的任一个、 time（游戏时间）
			/// PML, https://talk.letschatchat.com/smartbackend/pl/8it5zj9nypre8fxu6f4abk5tko
			// 游戏类型,	编号
			$res['game'] = null;
			$res['_game_description_id'] = null;
			if( ! empty($res['game_list']) ){
				$res['time'] = $res['game_list'][0]['betting_datetime'];
				$res['_game_description_id'] = $res['game_list'][0]['game_description_id'];
			}
			if( ! empty($res['game_type_list']) ){
				if( empty($res['time']) ){
					$res['time'] = $res['game_type_list'][0]['betting_datetime'];
					$res['_game_description_id'] = $res['game_type_list'][0]['game_description_id'];
				}else{
					// get the latest time
					$time_dateTime = new DateTime($res['time']);
					if ($time_dateTime->diff(new DateTime($res['game_type_list'][0]['betting_datetime']))->format('%R') == '+') {
						$res['time'] = $res['game_type_list'][0]['betting_datetime'];
						$res['_game_description_id'] = $res['game_type_list'][0]['game_description_id'];
					}
				}
			}

			if( ! empty($res['_game_description_id']) ){
				$res['game'] = $this->_mapping_to_game_no($res['_game_description_id']);
			}

			if( ! $reveal_list){
				/// remove for not required
				unset($res['game_list']);
				unset($res['game_list_isCached']);
				unset($res['game_type_list']);
				unset($res['game_type_list_isCached']);
				unset($res['_game_description_id']);
			}



			// If everything goes alright
			$ret = [
				'success'	=> true ,
				'code'		=> $this->errors['SUCCESS'] ,
				'mesg'		=> 'The api, bet_info_in_latest_time retrieved successfully',
				'result'	=> $res
			];

		}
	    catch (Exception $ex) {
	    	$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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

	} // EOF bet_info_in_latest_time

	public function bet_amount(){
		$std_creds = [];
		try {
			$this->load->model([ 'common_token', 'player_model', 'player_latest_game_logs' ]);
			$reveal_list = $this->input->get_post('reveal_list', true);
			$token = $this->input->post('token', true);
			$sign = $this->input->post('sign', true);
			$std_creds['lineNo'] = 409;
			$this->apply_api_acl('bet_amount', 'bet_amount_of_smash_promo_auth', [$token, $sign]);

			$this->set_ttl_in_smash_promo();

			// $playerId = $this->input->get_post('playerId');
			$player_id = $this->common_token->getPlayerIdByToken($token);
			$isValidToken = $this->_isValidToken($token, $player_id, $sign);
			if ( ! $isValidToken ) {
				$std_creds['lineNo'] = 439;
				throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
			}

			$res = [];
			$res['uid'] = $player_id; // uid（用户ID）、
			$res['betAmount'] = 0;
			$player = $this->player_model->getPlayerById($player_id);
			$player_username = $player->username;

			$use_start_date_today = $game_type_list = $this->utils->getConfig('player_center_api_bet_amount_use_start_date_today');
			$game_type_list = $this->utils->getConfig('player_center_api_bet_amount_game_type_list');
			$use_limit = 999999;
			$isCached = false; // For collect the result is Cached or Not, default should be false to apply.
			$cacheOnly=false; // the ttl, please reference to sync_latest_game_records_cache_ttl in config
			$forceRefresh = false;

			$isCached = false; // reset to default for player_latest_game_logs::get_latest_bets_by_player_and_game_type()
			$res['game_type_list'] = $this->player_latest_game_logs->get_latest_bets_by_player_and_game_type( $game_type_list // #1
																											, $player_username // #2
																											, $isCached // #3
																											, $forceRefresh // #4
																											, $cacheOnly // #5
																											, $use_start_date_today // #6
																											, $use_limit // #7
																											, $this->ttl// #8
																										);
			$res['game_type_list_isCached'] = $isCached;
			$bet_amount_list = array_column($res['game_type_list'], 'bet_amount');
			$res['betAmount'] = array_sum($bet_amount_list);
			$res['betAmount'] = round($res['betAmount'], 2);

			if( ! $reveal_list){
				/// remove for not required
				unset($res['game_type_list']);
				unset($res['game_type_list_isCached']);
			}

			// If everything goes alright
			$ret = [
				'success'	=> true ,
				'code'		=>$this->errors['SUCCESS'] ,
				'mesg'		=> 'The api, betamount retrieved successfully',
				'result'	=> $res
			];

		}
		catch (Exception $ex) {
			$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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

	} // EOF betamount


    public function invite_friend(){
		$disableCache = false;
		$success = false;
		$referral_infos = [];
		$std_creds = [];
		$ret = [];
		try{

			$this->load->model([ 'common_token', 'player_friend_referral', 'player_model' ]);
			$token = $this->input->post('token', true);
			$sign = $this->input->post('sign', true);
			$reveal_details = $this->input->get_post('reveal_details', true);
			$std_creds['lineNo'] = 491;
			$this->apply_api_acl('invite_friend', 'invite_friend_of_smash_promo_auth', [$token, $sign]);

			$this->set_ttl_in_smash_promo();

			$playerId = $this->common_token->getPlayerIdByToken($token);
			$isValidToken = $this->_isValidToken($token, $playerId, $sign);
			if ( ! $isValidToken ) {
				$std_creds['lineNo'] = 525;
				throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
			}
			$result = [];
			$cache_key = "getInvitefriend-referralInfos-{$playerId}";
			$referralInfosCache = $this->utils->getJsonFromCache($cache_key);

			if (empty($referralInfosCache) || $disableCache == true) {
				$_referral_infos = $this->player_friend_referral->getPlayerReferralList($playerId,  Player_friend_referral::STATUS_PAID);
				$referral_infos = [];
				foreach ($_referral_infos as $key => $value) {
					list($bets, $deposit) = $this->player_model->getBetsAndDepositByDate($value->invitedUserId, $value->invitedTime, $this->getNowForMysql());
					$referral_infos[] = [
						'invitedUserId' => $value->invitedUserId, // invitedUserId（被邀请ID）
						'invitedTime' => $value->invitedTime, // invitedTime（发起邀请时间）
						'registerTime' => $value->registerTime, // registerTime（被邀请用户注册时间）
						// 'status' => $value->status,
						'depositAmount' => $deposit, // depositAmount（被邀请用户存款金额）
						'depositTime' => $this->player_model->getPlayerFirstDepositDate($value->invitedUserId), // depositTime（被邀请用户存款时间）
					];
				}
				$result['list_isCached'] = false;
				$this->utils->saveJsonToCache($cache_key, $referral_infos, $this->ttl);
			} else {
				$referral_infos = $referralInfosCache;
				$result['list_isCached'] = true;
			}

			$result['uid'] = $playerId; // uid（用户ID）
			$result['list'] = $referral_infos; // 若结果为多条，放入list中即可

			if(!$reveal_details){
				/// remove for not required
				unset($result['list_isCached']);
			}

			$ret = [
				'success'	=> true ,
				'code'		=> $this->errors['SUCCESS'] ,
				'mesg'		=> 'The api, invite_friend retrieved successfully',
				'result'	=> $result
			];

		}
		catch (Exception $ex) {
			$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
			// {"message":"invite_friend","context":["Exception",{"code":1010,"message":"Invalid value for secure"},{"lineNo":514}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2022-11-02 10:39:27 904230","extra":{"tags":{"request_id":"d9d3e27433b04c9e15e41901984c7a63","env":"live.og_local","version":"6.178.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/player/application/controllers/customer_api/t1t_ac_tmpl.php","line":115,"class":"T1t_ac_tmpl","function":"_remap","url":"/api/smash/invite_friend","ip":"172.22.0.1","http_method":"POST","referrer":null,"host":"player.og.local","real_ip":null,"browser_ip":null,"process_id":25378,"memory_peak_usage":"2 MB","memory_usage":"2 MB"}}
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

    } // EOF invite_friend

    // http://player.og.local/api/smash/fake_mobi_push/hello
    // http://player.og.local/api/smash/fake_mobi_push/ok
    // http://player.og.local/api/smash/fake_mobi_push/200
    function fake_mobi_push($return_mode){
        $ret = [];
        switch($return_mode){
            case 'hello':
                $ret['success'] = true;
                $ret['code'] = 123;
                $ret['mesg'] = 'hello';
            break;

            case '200':
                $ret['code'] = 200;
                $ret['status'] = 'success';
            break;
            default:
            case 'ok':
                $ret['publishId'] = "pubid-90a1e81a-79e8-4bd5-b209-fa88c804c689";
            break;
        }

		return $this->comapi_return_json($ret);
    }
} // End trait t1t_comapi_module_player_password