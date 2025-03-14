<?php

/**
 * Class player_auth_module
 *
 * General behaviors include :
 *
 * * Login player
 * * Logout player
 * * Register
 * * Check if exist (email, username, contact, referral )
 * * Check sms verification
 * *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 *
 * @property Player_library $player_library
 */
trait player_auth_module {

	/**
	 * overview : login user from iframe
	 *
	 * @return postMessage to parent
	 *
	 */
	public function iframe_login() {
        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		//check login
		//set player info
		$origin = '*';
		$result = array('act' => $this->input->post('act'));

		if ($this->authentication->isLoggedIn()) {
			$result['success'] = true;
		}else{
            if (!$this->isPostMethod()) {
                return show_error('No permission', 403);
            }
			if($this->utils->getConfig('disable_player_register_and_login')){
				return show_error('No permission', 403);
			}
			
            if(!$this->_verifyExistSimpleCSRF()){
                return show_error('No permission', 403);
            }

            if($this->_isBlockedByReferrerRule()){
                return show_error('No permission', 403);
            }

            if($this->_isBlockedPlayer()){
                return show_error('No permission', 403);
            }
            $this->load->model(['operatorglobalsettings', 'player', 'ip']);
            $this->load->library(['player_library']);

            //check white ip
            $isWhiteIP=$this->utils->getConfig('enabled_white_ip_on_login') && $this->ip->isWhiteIPForPlayer();
            $username = $this->input->post('login');
            $isAutoMachineUser = $this->player->isAutoMachineUser($username);
            $ignoreACL=$isWhiteIP || $isAutoMachineUser;
            $acl_login_for_domain_list_only=$this->utils->getConfig('acl_login_for_domain_list_only');
            $this->utils->debug_log('acl_login_for_domain_list_only', $acl_login_for_domain_list_only);
            if(!$ignoreACL && !empty($acl_login_for_domain_list_only)){
                $host=$this->utils->getHttpHost();
                if(!empty($host) && $host!='localhost'){
                    $this->utils->debug_log('check acl domain', $host, $acl_login_for_domain_list_only);
                    if(in_array($host, $acl_login_for_domain_list_only)){
                    }else{
                        $this->utils->debug_log('not in list, ignore acl');
                        $ignoreACL=true;
                    }
                }else{
                    $ignoreACL=true;
                }
            }
            $this->utils->debug_log('ignoreACL', $ignoreACL, 'isWhiteIP', $isWhiteIP, 'isAutoMachineUser', $isAutoMachineUser, 'username', $username);
            if (!$ignoreACL && static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'iframe_login')) {
                $this->utils->debug_log('block login on iframe_login', $username, $this->utils->getIP());
                $blockedIp=$this->utils->getIp();
                $blockedRealIp=$this->utils->tryGetRealIPWithoutWhiteIP();
                $succBlocked=$this->player_model->writeBlockedPlayerRecord($username, $blockedIp,
                    $blockedRealIp, Player_model::BLOCKED_SOURCE_WWW_LOGIN);
                if(!$succBlocked){
                    $this->utils->error_log('write blocked record failed', $username, $blockedIp, $blockedRealIp);
                }
                return show_error('No permission', 403);
            }

			$httpHeaderInfo = $this->utils->getHttpOnRequest();

            // Deprecated, if need to trigger login failed event, create a new event
			// if (!$ignoreACL && static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'login_notify')) {
            //     $this->player_library->triggerPlayerLoginEvent('login using username:'. $username, $httpHeaderInfo['ip'], null);
			// }

            // if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled') && !$isAutoMachineUser && $this->input->post('captcha')) {
            // if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled') && !$isAutoMachineUser && array_key_exists('captcha', $this->input->post()) ) {
            if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled') && !$isAutoMachineUser ) {
				$namespace = $this->utils->getConfig('enabled_check_captcha_static_site');
                $this->form_validation->set_rules('captcha', lang('label.captcha'), "callback_check_captcha[$namespace]");
            }

            $this->form_validation->set_rules('login', 'lang:username', 'trim|required|xss_clean');
            $this->form_validation->set_rules('password', 'lang:password', 'trim|required|xss_clean');
            if (!$this->form_validation->run()) {
                $captcha_err = form_error('captcha');
                $errors =  validation_errors();
                $result['success'] = false;
                if (!empty($captcha_err)) {
                    $result['message'] = lang('error.captcha');
                } else {
                    $result['message'] = (is_array($errors) && !empty($errors)) ? end($errors) : strip_tags($errors);
                }
				$result = str_replace("'", "\\'", json_encode($result));
                $result = str_replace("\\n", '\\\\n', $result);
                $data = array('result' => $result, 'origin' => $origin);
                //NOTICE: don't callback with big text
                //callback
                return $this->load->view('player/iframe_callback', $data);
            }

            $username = $this->form_validation->set_value('login');
            $password = $this->form_validation->set_value('password');

			$remember_me = $this->input->post('remember_me');
			$rmb_token = $token = $this->input->cookie('remember_me');
			$this->utils->debug_log('auth-login-post', 'remember_me-token', $token);
			if (!empty($rmb_token)) {
				$password_holder = $this->session->userdata('password_holder');
				$this->utils->debug_log('auth-login-post', 'password-holder', $password_holder);
				if ($password_holder == $password) {
					$this->load->model('player_login_token');
					$player_id = $this->CI->player_login_token->getPlayerId($token);
					$password = $this->CI->player_model->getPasswordById($player_id);
				}
			}

            $login_result = $this->player_library->login_by_password($username, $password, $remember_me);

            if($login_result['success']){
                $result['success'] = TRUE;
                $result['message'] = NULL;

                $player_id = $login_result['player_id'];
                $source_method = __METHOD__;
                $this->player_library->triggerPlayerLoggedInEvent( $player_id, $source_method);

            }else{
                $result['success'] = FALSE;
				$result['message'] = (is_array($login_result['errors']) && !empty($login_result['errors'])) ? end($login_result['errors']) : $login_result['errors'];
            }

   //          if ($this->utils->getConfig('enable_player_login_report')) {
			// 	$this->load->model(array('player_login_report','player_model'));
			// 	$playerId = $this->player_model->getPlayerIdByUsername($username);
			// 	$this->utils->savePlayerLoginDetails($playerId, $username, $login_result, Player_login_report::LOGIN_FROM_PLAYER);
			// 	$this->utils->debug_log(__METHOD__,'savePlayerLoginDetails', $login_result);
			// }
        }

		if ($result['success']) {
			$playerId = $this->authentication->getPlayerId();
			//load player info
			$player = $this->player_functions->getPlayerById($playerId, null);
			$result['playerName'] = $player['username'];
			$result['playerId'] = $playerId;
			//add token
			$result['token'] = $this->authentication->getPlayerToken();

			$this->load->model(array('wallet_model'));
			$big_wallet = $this->utils->getSimpleBigWallet($playerId);;

			$result['VIP_group'] = $this->authentication->getPlayerMembership();
			$result['total_balance'] = $big_wallet['total_balance']['balance'];
			$result['walletInfo'] = $big_wallet;
			$result['player_active_profile_picture'] = $this->utils->setProfilePicture();
			// $result['popupBanner'] = $this->utils->getActivePopupBanner($playerId);
		}

		//dele return message a tag
		$stripped_target_str = strip_tags($result['message']);
		if($stripped_target_str != $result['message']){
			$result['message']=$stripped_target_str;
		}

		$data = array('result' => str_replace("'", "\\'", json_encode($result)), 'origin' => $origin);
		//NOTICE: don't callback with big text
		//callback
		$httpHeaderInfo = $this->utils->getHttpOnRequest();
		$this->utils->debug_log('<----------------- referrer origin ----------->', $httpHeaderInfo['referrer']);
		$this->utils->debug_log('<----------------- referrer player ----------->', $this->utils->getSystemUrl('www'));

		$this->load->view('player/iframe_callback', $data);
	}

	public function bind_register_proccess($player_id = null, &$data = null) {

		$this->process3rdPartyAffiliate($player_id, $data);
		$this->process3rdTrackinginfo($player_id, $data);
	}
	public function process3rdPartyAffiliate($player_id = null, &$data = null){
		if($this->utils->getConfig('enable_3rd_party_affiliate')) {

			$clickid = null;
			$pub_id = null;
			$rec = null;
			$source_config_key = null;

			parse_str($_SERVER['QUERY_STRING'], $query_params);
			if ($this->check_track_params($query_params)) {
				$clickid = $query_params['clickid'];
				$rec = $query_params['rec'];
			}
			$reg_track_cookie = [];
			if (!empty($_COOKIE['reg_track'])) {
				$reg_track_cookie = json_decode($_COOKIE['reg_track'], true);
				$clickid = $clickid?:$reg_track_cookie['clickid'];
				$rec = $rec?:$reg_track_cookie['rec'];
			}
			if(!empty($player_id)) {
				$clickid = $this->input->post('clickid')?:$clickid;
				$rec = $this->input->post('rec')?:$rec;
			}
			if (!empty($data)) {
				$this->template->add_js($this->utils->jsUrl('cpa_api/thirdpartyAffiliate.js'));
				$data['clickid'] = $clickid;
				$data['pub_id'] = $pub_id;
				$data['rec'] = $rec;
				return true;
			}
			if(empty($clickid)) {
				return false;
			}
			if(!empty($rec)) {
				$source_config_key = $this->cpaNetworkSourceMapping($rec) ? : $rec;
				if (!$this->utils->getConfig($source_config_key)) {
                    return false;
				}
				if (!empty($reg_track_cookie) && $clickid == $reg_track_cookie['clickid'] && $rec == $reg_track_cookie['rec']) {
					$cpaInfo = json_encode($reg_track_cookie);
				} else {
					$cpaInfo = json_encode(array('rec' => $rec,'clickid' => $clickid));
				}
			} else if($this->utils->getConfig('enable_adcombo')) {
				$rec = 'adcombo';
                if (!$this->utils->getConfig($rec)) {
					return false;
                }
				$pub_id = $this->input->post('pub_id')?:null;
				$query_params = array('esub'=>$clickid, 'pub_id'=>$pub_id, 'rec'=>$rec);
                $cpaInfo = json_encode($query_params);
			}

			if (!empty($player_id)) {
				$apiName = $source_config_key.'_api';
				$classExists = file_exists(strtolower(APPPATH . 'libraries/cpa_api/' . $apiName . ".php"));
				if(!$classExists){
					return;
				}
				$this->CI->load->library(array('cpa_api/'.$apiName));
				$trackApi = $this->CI->$apiName;

				$this->utils->debug_log("============Trackreg============". "clickid:[$clickid]");
				$this->player_model->updateCPAId($player_id, $cpaInfo);

				$playerInfo = $this->player_model->getPlayerById($player_id);
				$result_postBack = $trackApi->regPostBack($clickid, $playerInfo);

				$this->utils->debug_log('============result_postBackTrackreg============', $result_postBack);
			}
			return true;
		}
	}

	public function process3rdTrackinginfo($player_id = null, &$data = null)
	{

		if(!$player_id) {
			return true;
		}

		$tracking_token = empty($_COOKIE['_og_tracking_token'])?false:$_COOKIE['_og_tracking_token'];
		if (empty($tracking_token)) {
            if ($this->session->userdata('tracking_token')) {
                $tracking_token = $this->session->userdata('tracking_token');
            } else if ($this->input->get('tracking_token')) {
                $tracking_token = $this->input->get('tracking_token');
            } else {
				$httpHeadrInfo = $this->session->userdata('httpHeaderInfo') ? : $this->utils->getHttpOnRequest();
				if($this->utils->safeGetArray($httpHeadrInfo, '_og_tracking_token')){
					$tracking_token    = preg_replace('/\s+/', '', $httpHeadrInfo['_og_tracking_token']);
				}
			}
		}
		$this->utils->debug_log("process3rdTrackinginfo playerid: [$player_id], token: [$tracking_token]");

		if(!$tracking_token) {
			return true;
		}
		$this->load->library(['player_trackingevent_library']);
		$this->player_trackingevent_library->updateTrackingInfo($player_id, $tracking_token);
	}

	public function reg_track($tracking_code = null) {
        $this->output->set_header('Access-Control-Allow-Origin:*');
		if($this->utils->getConfig('enable_3rd_party_affiliate')) {
			parse_str($_SERVER['QUERY_STRING'], $query_params);
			if($this->check_track_params($query_params)) {
				set_cookie('reg_track', json_encode($query_params), 3600 * 24);
				$network_source = $query_params['rec'];
				$clickid = $query_params['clickid'];
				$initTracking = isset($query_params['initTracking']) ? ($query_params['initTracking'] == 1) : false;
				$source_config_key = $this->cpaNetworkSourceMapping($network_source) ? : $network_source;
				if($this->utils->getConfig($source_config_key)){
					$aff_network_setting = $this->utils->getConfig($source_config_key);
					$query_string = array('rec' => $network_source, 'clickid' => $clickid);
					if($tracking_code != null || $this->utils->getConfig('assigned_affiliate_for_affiliate_network')){
						$this->load->model(array('affiliatemodel'));
						$affid = $this->utils->getConfig('assigned_affiliate_for_affiliate_network'); //default affiliate
						$assigned_tracking_code = $this->affiliatemodel->getTrackingCodeByAffiliateId($affid);
						if(isset($aff_network_setting['assigned_affiliate_for_affiliate_network'])) {
							$assigned_tracking_code = $this->affiliatemodel->getTrackingCodeByAffiliateId($aff_network_setting['assigned_affiliate_for_affiliate_network']);
						}
						$tc = $tracking_code ?: $assigned_tracking_code;
						set_cookie('_og_tracking_code', $tc, 3600 * 24);
						if ($this->input->is_ajax_request() || $initTracking) {
							$this->returnJsonpResult(array('success' => true));
							return;
						} else {
							return redirect('player_center/iframe_register/'.$tc.'?'. http_build_query($query_string));
						}
					}
					if ($this->input->is_ajax_request() || $initTracking) {
						$this->returnJsonpResult(array('success' => true));
						return;
					} else {

						return redirect('player_center/iframe_register?'. http_build_query($query_string));
					}
				}
				return show_404();
			}
			return show_404();
		}
		return show_404();
	}

	public function cpaNetworkSourceMapping($rec){
		$current_source = false;
		$mapping_array = $this->config->item('network_source_mapping_array');
		if($mapping_array) {
			$current_source = array_key_exists($rec, $mapping_array) ? $mapping_array[$rec] : false;
		}
		return $current_source;
	}

	public function check_track_params($query_params) {
		if(!empty($query_params) && !empty($query_params['rec']) && !empty($query_params['clickid'])) {
			return true;
		}
		return false;
	}

	/**
	 * overview : iframe register
	 * @param string $tracking_code
	 * @param string $tracking_source_code
	 */
	public function iframe_register($tracking_code = null, $tracking_source_code = null, $agent_tracking_code = null, $agent_tracking_source_code = null) {
		$this->output->set_header('Access-Control-Allow-Origin:*');
		$this->load->model(array('operatorglobalsettings', 'registration_setting','group_level', 'affiliatemodel', 'agency_model', 'http_request'));

		if($this->utils->getConfig('disable_player_register_and_login')){
			return show_error('No permission', 403);
		}
        if($this->utils->getConfig('disable_player_register_and_redirect_to_login')){
            return redirect('/iframe/auth/login');
        }

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		if($tracking_code=='_null'){
			$tracking_code=null;
		}
		if($tracking_source_code=='_null'){
			$tracking_source_code=null;
		}
		if($agent_tracking_code=='_null'){
			$agent_tracking_code=null;
		}
		if($agent_tracking_source_code=='_null'){
			$agent_tracking_source_code=null;
		}

		if($this->authentication->isLoggedIn()){
            $this->goPlayerHome();
		}

        $this->clearSmsTime();

		$username = '';
		$data['hiddenPassword'] = $this->player_functions->randomizer($username);

		$tc = null;
		if (!empty($tracking_code)) {
			$tc = $tracking_code;
		}

		if (empty($tracking_source_code)) {
			$tracking_source_code = $this->input->get('source_code');
		}
		if (empty($tracking_source_code)) {
            if ($this->session->userdata('tracking_source_code')) {
                $tracking_source_code = $this->session->userdata('tracking_source_code');
            } else if ($this->input->get('tracking_source_code')) {
                $tracking_source_code = $this->input->get('tracking_source_code');
            }
		}

		if(empty($tc)){
			//load from order
			$tc=$this->getTrackingCode();
		}

		if(empty($agent_tracking_code)){
			$agent_tracking_code=$this->getAgentTrackingCode();
		}
		if(empty($agent_tracking_source_code)){
			$agent_tracking_source_code=$this->getAgentTrackingSourceCode();
		}

		//setup header
        $httpHeaderInfo = $this->utils->getHttpOnRequest();
        //ignore referrer is register
        if(strpos($httpHeaderInfo['referrer'], 'register')===FALSE){
	        $this->session->set_userdata('httpHeaderInfo', $httpHeaderInfo);
        }

        if (empty($tc) && !empty($httpHeaderInfo['referrer'])) {
            //try from referer
            $referrer=$httpHeaderInfo['referrer'];
            $host=parse_url($referrer, PHP_URL_HOST);
            if (!empty($host)) {
                $tc=$this->affiliatemodel->getTrackingCodeFromAffDomain($host);
                if (empty($tc)) {
                    $tc='';
                }
            }
		}

		$this->utils->debug_log('trackingCodeSessiontRegisterPage ', $tc);

		if (empty($tc) && !empty($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $query_params);
			if (isset($query_params['code'])) {
				$tc = $query_params['code'];
			}
			if (isset($query_params['source'])) {
				$tracking_source_code = $query_params['source'];
			}
		}

		$referralCode = $this->input->get('referralcode') ?: $this->utils->getReferralCodeCookie();
		if(empty($referralCode) && !empty($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $query_params);
			if (isset($query_params['referralcode'])) {
				$referralCode = $query_params['referralcode'];
			}
		}

		$this->utils->debug_log('iframe_register ---- serverQueryString ', $_SERVER['QUERY_STRING']);
		$this->session->set_userdata('httpHeaderInfo', $httpHeaderInfo);


        // -- BTAG FOR INCOME ACCESS
        $btag = '';
		if($this->utils->isEnabledFeature('enable_income_access')){
			$btag = $this->input->get('btag') ? : $this->utils->getBtagCookie();
		}

		$this->_process_register($data, $tc, $tracking_source_code, $agent_tracking_code, $agent_tracking_source_code, $referralCode, $btag);

		$prefixOfPlayer=$this->getPrefixUsernameOfPlayer($tc);
		$data['prefixOfPlayer'] =$prefixOfPlayer;
		$data['default_lang'] = $this->config->item('default_lang');

		$this->setTrackingCodeToSession($data['tracking_code']);

        $visit_record_id = $this->session->userdata('visit_record_id');

        #if there aff tracking code, insert without player first and then update it when player registered  with player id
        if(!empty($tc)){
            if(($this->utils->getConfig('track_visit_only_once') && $visit_record_id == false) || !$this->utils->getConfig('track_visit_only_once')) {
                $visit_record_id = $this->http_request->recordPlayerRegistration(null,$tc,$tracking_source_code);
                $this->session->set_userdata('visit_record_id',$visit_record_id);
            }
        }

		$data['snackbar'] = isset($snackbar) ? $snackbar : [];
		$common_country_list = unserialize(COMMON_COUNTRY_LIST);
		$country_list = unserialize(COUNTRY_LIST);
		$block_country_list = $this->utils->getConfig('block_country_list');
		if (!empty($block_country_list))
		foreach($block_country_list as $block) {
			if (in_array($block, $common_country_list)) {
				unset($common_country_list[$block]);
			}
			if (in_array($block, $country_list)) {
				unset($country_list[$block]);
			}
		}
		$usernameRegDetails = []; // for collect via utils::getUsernameReg()
		$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
		$data['playerId'] = NULL;
		$data['common_country_list'] = $common_country_list;
		$data['country_list'] = $country_list;
		$data['currency_list'] = $this->config->item('currency_list');
		$data['forbidden_names'] = $this->utils->getConfig('default_forbidden_names');
		$data['min_username_length'] = $this->utils->getConfig('default_min_size_username');
		$data['max_username_length'] = $this->utils->getConfig('default_max_size_username');
		$data['min_password_length'] = !empty($password_min_max_enabled) ? $password_min_max_enabled['min'] : $this->utils->getConfig('default_min_size_password');
		$data['max_password_length'] = !empty($password_min_max_enabled) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');
		$data['regex_username'] = $this->utils->getUsernameReg($usernameRegDetails);
		$data['username_case_insensitive'] = $usernameRegDetails['username_case_insensitive'];
		$data['username_requirement_mode'] = $usernameRegDetails['username_requirement_mode'];
		$data['validateUsername01'] = lang('validation.validateUsername01');
		$data['validateUsername02'] = lang('validation.validateUsername02');
		switch($usernameRegDetails['username_requirement_mode']){
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBER_ONLY:
				$data['validateUsername01'] = lang('validation.validateUsername01_MODE_NUMBER_ONLY');
				$data['validateUsername02'] = lang('validation.validateUsername02_MODE_NUMBER_ONLY');
				break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_LETTERS_ONLY:
				$data['validateUsername01'] = lang('validation.validateUsername01_MODE_LETTERS_ONLY');
				$data['validateUsername02'] = lang('validation.validateUsername02_MODE_LETTERS_ONLY');
				break;
			case Operatorglobalsettings::USERNAME_REQUIREMENT_MODE_NUMBERS_AND_LETTERS_ONLY:
				$data['validateUsername01'] = lang('validation.validateUsername01_MODE_NUMBERS_AND_LETTERS_ONLY');
				$data['validateUsername02'] = lang('validation.validateUsername02_MODE_NUMBERS_AND_LETTERS_ONLY');
				break;
		}

		$data['regex_password'] = $this->utils->getPasswordReg();
		$data['min_security_answer_length'] = $this->utils->getConfig('default_min_size_security_answer');
		$data['max_security_answer_length'] = $this->utils->getConfig('default_max_size_security_answer');
		$data['min_first_name_length'] = $this->utils->getConfig('default_min_size_first_name');
        $data['max_first_name_length'] = $this->utils->getConfig('default_max_size_first_name');
		$data['max_last_name_length'] = $this->utils->getConfig('default_max_size_last_name');

		$data['currentLang'] = $this->language_function->getCurrentLanguage();
		$data['registration_fields'] = $this->registration_setting->getRegistrationFields();

		$data['default_prefix_for_username'] = $this->config->item('default_prefix_for_username');
		$data['captcha_registration'] = $this->operatorglobalsettings->getSettingJson('registration_captcha_enabled');
        $data['full_address_in_one_row'] = $this->operatorglobalsettings->getSettingJson('full_address_in_one_row');
		$data['showSMSField'] = !$this->utils->getConfig('disabled_sms') && $this->registration_setting->isRegistrationFieldVisible('SMS Verification Code');
		$data['showWithdrawalPasswordField'] = ($this->utils->getConfig('withdraw_verification') == 'withdrawal_password') && $this->registration_setting->isRegistrationFieldVisible('Withdrawal Password');

		$active = $this->config->item('si_active');
		$current_host = $this->utils->getHttpHost();
		$active_domain_assignment = $this->config->item('si_active_domain_assignment');
		if( ! empty($active_domain_assignment[$current_host]) ){
			$active = $active_domain_assignment[$current_host];
		}
		$data['captcha_length'] = $this->config->item($active)['code_length'];

		$data['getAllCanJoinIn'] = $this->group_level->getAllCanJoinInGroup();
		$data['age_limit'] = $this->operatorglobalsettings->getSettingValue('registration_age_limit');
		$this->language_function->setCurrentLanguage($data['currentLang']);

		$bankTypes = $this->player_functions->getAllBankType();
		$data['banks'] =  $bankTypes;
		$new_banktypes_order = [];

		//Note: for arranging banktypes order in dropdown, for example in korea OGP-1017
		$target_banktypes_order = $this->utils->getConfig('target_banktypes_order');
		if(count($target_banktypes_order) > 0 ){

			foreach ($target_banktypes_order  as $value) {
				foreach ($bankTypes as $k => $v) {
					if($v['bankTypeId'] == $value){
						array_push($new_banktypes_order, $v);
						unset($bankTypes[$k]);
					}
				}
			}

			$data['banks'] = array_merge($new_banktypes_order,$bankTypes);
		}

		$this->loadTemplate(lang('Register'), '', '', 'player');
		# add bootstrap-select css, js for dialing_code
		$base_path = base_url() . $this->utils->getPlayerCenterTemplate(false);
		$this->template->add_js($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.js'));
		$this->template->add_css($this->utils->thirdpartyUrl('bootstrap-select/1.12.4/bootstrap-select.min.css'));

		$this->CI->load->library(['iovation_lib']);
		$data['is_iovation_enabled'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_registration') && $this->CI->iovation_lib->isReady;
		if($data['is_iovation_enabled']){
			if($this->utils->getConfig('iovation')['use_first_party']){
				$this->template->add_js($this->utils->jsUrl($this->utils->getConfig('iovation')['first_party_js_config']));
			}else{
				$this->template->add_js($this->utils->jsUrl('config.js'));
			}
			$this->template->add_js($this->utils->jsUrl('iovation.js'));
		}

		$this->bind_register_proccess(null, $data);

		$regPathDir = realpath(rtrim(dirname(__FILE__).'/../../../../player/application/views/resources/common/auth/', '/'));
		$this->CI->load->model(['operatorglobalsettings']);

        $data['age_limit'] = $this->operatorglobalsettings->getSettingIntValue('registration_age_limit', '18');
		$data['age_limit_num'] = intval($data['age_limit']);

		$this->CI->load->library(['iovation_lib']);
		$data['is_iovation_enabled'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_registration') && $this->CI->iovation_lib->isReady;

        $referrer = [
            'btag'                       => $data['btag'],
            'agent_tracking_code'        => $data['agent_tracking_code'],
            'agent_tracking_source_code' => $data['agent_tracking_source_code'],
            'tracking_code'              => $data['tracking_code'],
            'tracking_source_code'       => $data['tracking_source_code'],
            'referral_code'              => $data['referral_code'],
		];
		$this->utils->debug_log('iframeRegisterReferrer', $referrer);
        $data['line_register_url'] = site_url('iframe/auth/line_login').'?'.http_build_query($referrer);

		$embedded_register_template_form = '';
		if($this->utils->is_mobile()){
            $embedded_register_template_form = 'register_mobile.php';
        }else{
            $embedded_register_template_form = 'register_recommended.php';

            $getRegistrationTemplate = $this->utils->getPlayerCenterRegistration();
            if(!empty($getRegistrationTemplate)){
                if(file_exists($regPathDir . '/register_' . $getRegistrationTemplate . '.php')){
                    $embedded_register_template_form = 'register_' . $getRegistrationTemplate . '.php';
                }
            }
		}

		if ( ! empty($this->utils->getConfig('enable_OGP19860')) ){
			#OGP-21850
			$data['account_validator'] = $this->financialAccountValidatorBuilder();
        } // EOF if ( ! empty($this->utils->getConfig('enable_OGP19860')) ){...

        if ( ! empty($this->utils->getConfig('enable_OGP19860'))
            || ! empty($this->utils->getConfig('enable_customized_register_template'))
        ){
			// Convert "recommended" to "mobile" while visit on mobile.
			$getRegistrationTemplate = $this->utils->getPlayerCenterRegistration();
			if($this->utils->is_mobile()){
				if(!empty($getRegistrationTemplate)){
					$getRegistrationTemplate = str_replace('recommended', 'mobile', $getRegistrationTemplate);
					$getRegistrationTemplatePathFile = 'register_' . $getRegistrationTemplate . '.php';
					if(file_exists($regPathDir . '/'. $getRegistrationTemplatePathFile)){
						$embedded_register_template_form = $getRegistrationTemplatePathFile;
					}
				}
			}
		} // EOF if ( ! empty($this->utils->getConfig('enable_OGP19860')) ){...

        $data['player_center_custom_script_in_specify_page'] = $this->utils->getTrackingScriptWithDoamin('player', 'exoclick', 'head');
		if(!empty($data['player_center_custom_script_in_specify_page']['iframe_register'])){
            $data['player_center_custom_script_in_specify_page'] = json_encode($data['player_center_custom_script_in_specify_page']['iframe_register']);
        }

        $data['append_js_content'] = null;
        $data['append_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'exoclick', 'head');
        if(!empty($data['append_js_filepath']['iframe_register'])){
            $data['append_js_content'] = $data['append_js_filepath']['iframe_register'];
            $this->template->add_js($data['append_js_content']);
        }

        $data['append_smash_js_content'] = null;
        $data['append_smash_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'tiktok', 'head');
        if(!empty($data['append_smash_js_filepath']['iframe_register'])){
            $data['append_smash_js_content'] = $data['append_smash_js_filepath']['iframe_register'];
            $this->template->add_js($data['append_smash_js_content']);
        }

        $data['append_ole777id_js_content'] = null;
        $data['append_ole777id_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'robot', 'head');
        if(!empty($data['append_ole777id_js_filepath']['iframe_register'])){
            $data['append_ole777id_js_content'] = $data['append_ole777id_js_filepath']['iframe_register'];
            $this->template->add_js($data['append_ole777id_js_content']);
        }

        $data['append_ole777thb_js_content'] = null;
        $data['append_ole777thb_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'gtm_iframe_register', 'head');
        if(!empty($data['append_ole777thb_js_filepath']['iframe_register'])){
            $data['append_ole777thb_js_content'] = $data['append_ole777thb_js_filepath']['iframe_register'];
            $this->template->add_js($data['append_ole777thb_js_content']);
        }

		$data['embedded_register_template_form'] = $regPathDir . '/' . $embedded_register_template_form;

		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate(FALSE) . '/auth/register' , $data);
		$this->template->render();
	}

	protected function _process_register(&$data, $_tracking_code, $_tracking_source_code, $_agent_tracking_code, $_agent_tracking_source_code, $_referral_code, $_btag = NULL){
        $tracking_code = NULL;
        $tracking_source_code = NULL;
        $displayAffilateCode = TRUE;

        $agent_tracking_code = NULL;
        $agent_tracking_source_code = NULL;
        $displayAgencyCode = TRUE;

        $referral_code = NULL;
        $displayReferralCode = TRUE;

        if(!empty($_tracking_code) || !empty($_tracking_source_code)){ // from affilate
            $tracking_code = $_tracking_code;
            $tracking_source_code = $_tracking_source_code;

            $displayAgencyCode = FALSE;
            $this->utils->clearAgentTrackingCodeFromSession();
            $this->utils->clearAgentTrackingSourceCodeFromSession();

            if($this->utils->isEnabledFeature('disable_display_affiliate_code_on_player_center_affiliate_register_page')){
                $displayReferralCode = FALSE;
                $this->utils->removeReferralCodeCookie();
            }
        }else if(!empty($_agent_tracking_code) || !empty($_agent_tracking_source_code)){ // from agency
            $displayAffilateCode = FALSE;
            $this->utils->clearTrackingCode();

            $agent_tracking_code = $_agent_tracking_code;
            $agent_tracking_source_code = $_agent_tracking_source_code;

            if($this->utils->isEnabledFeature('disable_display_agent_code_on_player_center_agent_register_page')){
                $displayReferralCode = FALSE;
                $this->utils->removeReferralCodeCookie();
            }
		}else{ // from player center
            if(!empty($_referral_code)){
                if($this->utils->isEnabledFeature('hidden_affiliate_code_on_player_center_when_exists_referral_code')){
                    $displayAffilateCode = FALSE;
                    $this->utils->clearTrackingCode();
                }

                if($this->utils->isEnabledFeature('hidden_agent_code_on_player_center_when_exists_referral_code')){
                    $displayAgencyCode = FALSE;
                    $this->utils->clearAgentTrackingCodeFromSession();
                    $this->utils->clearAgentTrackingSourceCodeFromSession();
                }

                $referral_code = $_referral_code;
                $this->utils->setReferralCodeCookie($_referral_code);
            }
		}

		if($this->utils->isEnabledFeature('force_using_referral_code_when_register') && !empty($_referral_code)) {

			$referral_code = $_referral_code;
			$displayReferralCode = TRUE;
			$this->utils->setReferralCodeCookie($_referral_code);
		}

        // -- BTAG FOR INCOME ACCESS
		if($this->utils->isEnabledFeature('enable_income_access')){
			if (!empty($_btag)) $this->utils->setBtagCookie($_btag);
		}

		$data['btag'] = $_btag;

        $data['tracking_code'] = $tracking_code;
        $data['tracking_source_code'] = $tracking_source_code;
        $data['displayAffilateCode'] = (!empty($tracking_code) || !empty($tracking_source_code)) ? FALSE : $displayAffilateCode;

        $data['agent_tracking_code'] = $agent_tracking_code;
        $data['agent_tracking_source_code'] = $agent_tracking_source_code;
        $data['displayAgencyCode'] = (!empty($agent_tracking_code) || !empty($agent_tracking_source_code)) ? FALSE : $displayAgencyCode;

        $data['referral_code'] = $referral_code;
        $data['displayReferralCode'] = (!empty($referral_code)) ? FALSE : $displayReferralCode;
    }

	/**
	 * Cloned from Player_center::financialAccountValidatorBuilder()
	 *
	 * @param string $payment_type_flag
	 * @return array $bank_card_validator
	 */
	public function financialAccountValidatorBuilder($payment_type_flag = '1'){
		$this->CI->load->model(['financial_account_setting']);
        $financial_account_rule = $this->financial_account_setting->getPlayerFinancialAccountRulesByPaymentAccountFlag($payment_type_flag);

        $bank_card_validator = array();
        $bank_card_validator['only_allow_numeric'] = $financial_account_rule['account_number_only_allow_numeric'];
        $bank_card_validator['allow_modify_name']  = $financial_account_rule['account_name_allow_modify_by_players'];
        $bank_card_validator['field_required']     = explode(',', $financial_account_rule['field_required']);
        $bank_card_validator['field_show']         = explode(',', $financial_account_rule['field_show']);

        $account_min = $financial_account_rule['account_number_min_length'];
        $account_max = $financial_account_rule['account_number_max_length'];
        $bank_card_validator['bankAccountNumber'] = [
            'required'       => TRUE,
            'min_max_length' => [$account_min, $account_max],
            'remote'         => '/api/bankAccountNumber',
            'error_remote'   => [
                'invalid' => 'account_number_can_not_be_duplicate',
                'valid'   => 'account_number_allow_used'
            ]
        ];
        $bank_card_validator['bankAccountFullName'] = [
            'min_max_length' => [1, 200]
        ];
        $bank_card_validator['bankAccountFullName'] = (in_array(Financial_account_setting::FIELD_NAME, $bank_card_validator['field_required'])) ?'': '';
        $bank_card_validator['phone']               = (in_array(Financial_account_setting::FIELD_PHONE, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['branch']              = (in_array(Financial_account_setting::FIELD_BANK_BRANCH, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['area']                = (in_array(Financial_account_setting::FIELD_BANK_AREA, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['bankAddress']         = (in_array(Financial_account_setting::FIELD_BANK_ADDRESS, $bank_card_validator['field_required'])) ? ['required' => true] : '';

        return $bank_card_validator;
    }

	/**
	 * overview : iframe register send sms verification
	 *
	 * @param string $mobileNumber
	 */
	public function iframe_register_send_sms_verification($mobileNumber = null, $restrictArea = null) {

		$this->load->library(array('session', 'sms/sms_sender' ,'voice/voice_sender'));
		$this->load->model(array('sms_verification', 'player_model'));
		$dialing_code = $this->input->post('dialing_code');
		// $disabled_sms_capcha = !empty($this->input->post('disabled_sms_capcha'))? $this->input->post('disabled_sms_capcha') : false ;

		if ($this->utils->getConfig('disabled_sms')) {
			return $this->returnJsonResult(array('success' => false, 'message' => lang('Disabled SMS')));
		}

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		$player_id = $this->authentication->getPlayerId();
		$player = $this->player_functions->getPlayerById($player_id);
		if (!isset($mobileNumber) || $mobileNumber == 'null') {
			# Try to get mobile number from player profile if not supplied
			$this->load->library(array('authentication', 'player_functions'));

			$mobileNumber = $player['contactNumber'];
			if (!$mobileNumber) {
				$this->returnJsonResult(array('success' => false, 'message' => lang('No contact number available')));
				return;
			}
		}

		if(!$this->utils->isEnabledFeature('disable_captcha_before_sms_send')){
			if (!$this->check_sms_captcha()) {
				$this->returnJsonResult(array('success' => false, 'message' => lang('error.captcha'), 'isDisplay' => true, 'field' => 'captcha'));
				return;
			}
		}

		$sessionId = $this->session->userdata('session_id');
		$lastSmsTime = $this->session->userdata('last_sms_time');
		$smsCooldownTime = $this->config->item('sms_cooldown_time');

		if(empty($lastSmsTime)){
			//load from redis
			$lastSmsTime=$this->utils->readRedis($mobileNumber.'_last_sms_time');
		}

		# Should not send SMS without valid session ID
		if(!$sessionId) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('Unknown error')));
			return;
		}

		# Check the send count with ip or mobile on cooldown period
		if ($this->sms_verification->checkIPAndMobileLastTIme($smsCooldownTime, $mobileNumber)) {
			$this->returnJsonResult(array('success' => false, 'message' => sprintf(lang('It is not allowed to send twice within %s seconds if same IP/Phone no, please try again later.'), $smsCooldownTime), 'isDisplay' => true));
			return;
		}

		# This check ensures for a given session (i.e. session ID), SMS cannot be sent again within the cooldown period
		if ($lastSmsTime && time() - $lastSmsTime <= $smsCooldownTime) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('You are sending SMS too frequently. Please wait.')));
			return;
		}

		$codeCount = $this->sms_verification->getVerificationCodeCountPastMinute();
		if($codeCount > $this->config->item('sms_global_max_per_minute')) {
			$this->utils->error_log("Sent [$codeCount] SMS in the past minute, exceeded config max [".$this->config->item('sms_global_max_per_minute')."]");
			$this->returnJsonResult(array('success' => false, 'message' => lang('SMS process is currently busy. Please wait.')));
			return;
		}

		$numCount = $this->sms_verification->getTodaySMSCountFor($mobileNumber);
		if($numCount >= $this->config->item('sms_max_per_num_per_day')) {
			$this->utils->error_log("Sent maximum [$numCount] SMS to this number today.");
			$this->returnJsonResult(array('success' => false, 'message' => sprintf(lang('One username is only allowed to send %s texts per day, please try again tomorrow.'), $this->config->item('sms_max_per_num_per_day')), 'isDisplay' => true));
			return;
		}

		// OGP-12 : Check for duplicate mobile number
		$playerFromMobile = $this->player_model->getPlayerLoginInfoByNumber($mobileNumber);
		if (is_array($playerFromMobile) && isset($playerFromMobile['playerId']) > 0 && $player_id != $playerFromMobile['playerId']) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('The number is in use'), 'isDisplay' => true));
			return;
		}

		# Topic : Restrict send num -- By Bryson
		# 1. Use system_feature to control
		# 2. Setting restrict num in config must more than zero
		# 3. Note : Get request params is restrict_num to control (Currently only the player center's phone verification settings)
		$restrictSmsSendNum = (int) $this->utils->getConfig('sms_restrict_send_num');
		$enableRestrictSmsSendNum = $this->utils->isEnabledFeature('enable_restrict_sms_send_num_in_player_center_phone_verification');
		$isRestrictArea = $this->sms_verification->isRestrictArea($restrictArea);
		if ($isRestrictArea && $player_id && $enableRestrictSmsSendNum && $restrictSmsSendNum > 0) {
			$numDaily = $this->sms_verification->sendNumDaily($player_id, $restrictArea);
			if ($numDaily >= $restrictSmsSendNum) {
				$this->returnJsonResult(array('success' => false, 'message' => lang('Today has exceeded the sending limit')));
				return;
			}
		}
		if($restrictArea == NULL) {
			$restrictArea = sms_verification::USAGE_DEFAULT;
		}
		$code = $this->sms_verification->getVerificationCode($player_id, $sessionId, $mobileNumber, $restrictArea);

        $sendSmsData = [];
        $use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
        if ($use_new_sms_api_setting) {
			#restrictArea = action type
			list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($player_id, $mobileNumber, $restrictArea, $sessionId);
			$this->utils->debug_log(__METHOD__, 'use new sms api',$useSmsApi, $sms_setting_msg, $restrictArea);

			if (empty($useSmsApi)) {
				$this->returnJsonResult(array('success' => false, 'message' => $sms_setting_msg));
				return;
			}
		}else{
			$useSmsApi = $this->sms_sender->getSmsApiName();
		}

        $msg = $this->utils->createSmsContent($code, $useSmsApi);
        $mobileNum = !empty($dialing_code)? $dialing_code.'|'.$mobileNumber : $mobileNumber;
        if( !empty($this->config->item('switch_voice_under_sms_limit_num_per_day'))){
        	if( $numCount >= $this->config->item('switch_voice_under_sms_limit_num_per_day')){
				$useVoiceApi = $this->voice_sender->getvoiceApiName();
				if($useVoiceApi != 'disable'){
					if ($this->voice_sender->send($mobileNum, $code, $useVoiceApi)) {
						$this->session->set_userdata('last_sms_time', time());
						$this->utils->writeRedis($mobileNumber.'_last_sms_time', time());
						$this->returnJsonResult(array('success' => true));
					} else {
						$this->returnJsonResult(array('success' => false, 'message' => $this->voice_sender->getLastError()));
					}
				return;
				}
			}
        }

		if ($this->utils->isEnabledFeature('enabled_send_sms_use_queue_server')) {
			$this->load->model('queue_result');
			$this->load->library('lib_queue');
			$content = $msg;
			$callerType = Queue_result::CALLER_TYPE_PLAYER;
			$caller = $player_id;
			$state = null;

			$this->lib_queue->addRemoteSMSJob($mobileNum, $content, $callerType, $caller, $state);

			$this->session->set_userdata('last_sms_time', time());
			$this->utils->writeRedis($mobileNumber.'_last_sms_time', time());
			$this->returnJsonResult(array('success' => true));

		} else {
			if ($this->sms_sender->send($mobileNum, $msg, $useSmsApi)) {
				$this->session->set_userdata('last_sms_time', time());
				$this->utils->writeRedis($mobileNumber.'_last_sms_time', time());
				$this->returnJsonResult(array('success' => true));
			} else {
				$this->returnJsonResult(array('success' => false, 'message' => $this->sms_sender->getLastError()));
			}
		}
	}

	/**
	 * overview : iframe register send voice verification
	 *
	 * @param string $mobileNumber
	 */
	public function iframe_register_send_voice_verification($mobileNumber = null, $restrictArea = null) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		$this->load->library(array('session', 'voice/voice_sender'));
		$this->load->model(array('sms_verification', 'player_model'));
		$dialing_code = $this->input->post('dialingCode');
		$disabled_sms_capcha = !empty($this->input->post('disabled_sms_capcha'))? $this->input->post('disabled_sms_capcha') : false ;
		$this->utils->debug_log(__METHOD__, 'dialingCode received', $dialing_code);

		$player_id = $this->authentication->getPlayerId();
		$player = $this->player_functions->getPlayerById($player_id);

		if (!isset($mobileNumber)) {
			# Try to get mobile number from player profile if not supplied
			$this->load->library(array('authentication', 'player_functions'));

			$mobileNumber = $player['contactNumber'];
			if (!$mobileNumber) {
				$this->returnJsonResult(array('success' => false, 'message' => lang('No contact number available')));
				return;
			}
		}

		if(!$disabled_sms_capcha){
			if (!$this->check_sms_captcha()) {
				$this->returnJsonResult(array('success' => false, 'message' => lang('error.captcha'), 'isDisplay' => true, 'field' => 'captcha'));
				return;
			}
		}

		$sessionId = $this->session->userdata('session_id');
		$lastSmsTime = $this->session->userdata('last_sms_time');
		$smsCooldownTime = $this->config->item('sms_cooldown_time');

		if(empty($lastSmsTime)){
			//load from redis
			$lastSmsTime=$this->utils->readRedis($mobileNumber.'_last_sms_time');
		}

		# Should not send SMS without valid session ID
		if(!$sessionId) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('Unknown error')));
			return;
		}

		# Check the send count with ip or mobile on cooldown period
		if ($this->sms_verification->checkIPAndMobileLastTIme($smsCooldownTime, $mobileNumber)) {
			$this->returnJsonResult(array('success' => false, 'message' => sprintf(lang('It is not allowed to send twice within %s seconds if same IP/Phone no, please try again later.'), $smsCooldownTime), 'isDisplay' => true));
			return;
		}

		# This check ensures for a given session (i.e. session ID), SMS cannot be sent again within the cooldown period
		if ($lastSmsTime && time() - $lastSmsTime <= $smsCooldownTime) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('You are sending Voice service too frequently. Please wait.')));
			return;
		}

		$codeCount = $this->sms_verification->getVerificationCodeCountPastMinute();
		if($codeCount > $this->config->item('sms_global_max_per_minute')) {
			$this->utils->error_log("Sent [$codeCount] Voice service in the past minute, exceeded config max [".$this->config->item('sms_global_max_per_minute')."]");
			$this->returnJsonResult(array('success' => false, 'message' => lang('Voice service process is currently busy. Please wait.')));
			return;
		}

		$numCount = $this->sms_verification->getTodaySMSCountFor($mobileNumber);
		if($numCount >= $this->config->item('sms_max_per_num_per_day')) {
			$this->utils->error_log("Sent maximum [$numCount] SMS to this number today.");
			$this->returnJsonResult(array('success' => false, 'message' => sprintf(lang('One username is only allowed to send %s texts per day, please try again tomorrow.'), $this->config->item('sms_max_per_num_per_day')), 'isDisplay' => true));
			return;
		}

		// OGP-12 : Check for duplicate mobile number
		$playerFromMobile = $this->player_model->getPlayerLoginInfoByNumber($mobileNumber);
		if (is_array($playerFromMobile) && isset($playerFromMobile['playerId']) > 0 && $player_id != $playerFromMobile['playerId']) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('The number is in use'), 'isDisplay' => true));
			return;
		}

		# Topic : Restrict send num -- By Bryson
		# 1. Use system_feature to control
		# 2. Setting restrict num in config must more than zero
		# 3. Note : Get request params is restrict_num to control (Currently only the player center's phone verification settings)
		$restrictSmsSendNum = (int) $this->utils->getConfig('sms_restrict_send_num');
		$enableRestrictSmsSendNum = $this->utils->isEnabledFeature('enable_restrict_sms_send_num_in_player_center_phone_verification');
		$isRestrictArea = $this->sms_verification->isRestrictArea($restrictArea);
		if ($isRestrictArea && $player_id && $enableRestrictSmsSendNum && $restrictSmsSendNum > 0) {
			$numDaily = $this->sms_verification->sendNumDaily($player_id, $restrictArea);
			if ($numDaily >= $restrictSmsSendNum) {
				$this->returnJsonResult(array('success' => false, 'message' => lang('Today has exceeded the sending limit')));
				return;
			}
		}
		if($restrictArea == NULL) {
			$restrictArea = sms_verification::USAGE_DEFAULT;
		}
		$code = $this->sms_verification->getVerificationCode($player_id, $sessionId, $mobileNumber, $restrictArea);
        $useSmsApi = $this->voice_sender->getvoiceApiName();
        $mobileNum = !empty($dialing_code)? $dialing_code.'|'.$mobileNumber : $mobileNumber;

        if ($this->voice_sender->send($mobileNum, $code, $useSmsApi)) {
				$this->session->set_userdata('last_sms_time', time());
				$this->utils->writeRedis($mobileNumber.'_last_sms_time', time());
				$this->returnJsonResult(array('success' => true));
			} else {
				$this->returnJsonResult(array('success' => false, 'message' => $this->voice_sender->getLastError()));
			}
	}

	/**
	 * overview : validate registration
	 *
	 * @param string $from_comapi
	 * @return array
	 */
	public function validate_registration($from_comapi = false, $ignore_registration_settings = false, $verify_formate_only = false) {

		$enable_form_validation_under_registration = $this->utils->getConfig('enable_form_validation_under_registration');

		if($ignore_registration_settings){
			$enable_form_validation_under_registration = ['username', 'password'];
		}

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		$min_username_length = $this->utils->getConfig('default_min_size_username');
		$max_username_length = $this->utils->getConfig('default_max_size_username');
		$min_password_length = $this->utils->getConfig('default_min_size_password');
		$max_password_length = $this->utils->getConfig('default_max_size_password');

		// apply isPasswordMinMaxEnabled().
		$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
		$min_password_length = !empty($password_min_max_enabled['min']) ? $password_min_max_enabled['min'] : $this->utils->getConfig('default_min_size_password');
		$max_password_length = !empty($password_min_max_enabled['max']) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');

		$this->load->library(array('form_validation','session'));
		$this->load->model(array('operatorglobalsettings', 'registration_setting','sms_verification'));

		if( in_array('username',$enable_form_validation_under_registration)
		) {

            $fieldname='username';
            $_ruleStrDefault = 'max_length';
            list($_isEnabled, $username_max_length_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // username_min_length
            $_ruleStrDefault = 'min_length';
            list($_isEnabled, $username_min_length_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // username_alpha_numeric
            $_ruleStrDefault = 'alpha_numeric';
            list($_isEnabled, $username_alpha_numeric_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // username_regex_match
            $_ruleStrDefault = 'regex_match';
            list($_isEnabled, $username_regex_match_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // username_check_username : 1/2
            $_ruleStrDefault = 'check_username';
            $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);

			$usernameRegDetails = []; // for collect via utils::getUsernameReg()
			$regex_username = $this->utils->getUsernameReg($usernameRegDetails);
            $rulesStr = "trim|xss_clean|required|{$username_alpha_numeric_rule}|{$username_min_length_rule}[{$min_username_length}]|{$username_max_length_rule}[{$max_username_length}]|{$username_regex_match_rule}[{$regex_username}]|callback_check_username";
			$this->form_validation->set_rules('username', lang('Username'), $rulesStr);

		}

        if( in_array('password',$enable_form_validation_under_registration)
		) {
            /// for password - set_message()
            // password_max_length
            $fieldname='password';
            $_ruleStrDefault = 'max_length';
            list($_isEnabled, $password_max_length_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // password_min_length
            $_ruleStrDefault = 'min_length';
            list($_isEnabled, $password_min_length_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // password_regex_match
            $_ruleStrDefault = 'regex_match';
            list($_isEnabled, $password_regex_match_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // password_not_matches[username]
            $_ruleStrDefault = 'not_matches[username]';
            list($_isEnabled, $password_not_matches_rule) = $this->form_validation->apply_rule_arg_by_field($_ruleStrDefault, $fieldname);

			$regex_password = $this->utils->getPasswordReg();
            $_rules = "trim|xss_clean|required|{$password_min_length_rule}[{$min_password_length}]|{$password_max_length_rule}[{$max_password_length}]|{$password_not_matches_rule}|{$password_regex_match_rule}[{$regex_password}]";
			$this->form_validation->set_rules('password', lang('Password'), $_rules);
		}

		// form_validation messages
		$this->form_validation->set_message('min_length', lang('formvalidation.min_length'));
		$this->form_validation->set_message('max_length', lang('formvalidation.max_length'));

		if( in_array('password',$enable_form_validation_under_registration)
			&& in_array('cpassword',$enable_form_validation_under_registration)
		) {
            $fieldname='cpassword';
            // cpassword_max_length
            $_ruleStrDefault = 'max_length';
            list($_isEnabled, $cpassword_max_length_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // cpassword_min_length
            $_ruleStrDefault = 'min_length';
            list($_isEnabled, $cpassword_min_length_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);
            // cpassword_matches
            $_ruleStrDefault = 'matches';
            list($_isEnabled, $cpassword_matches_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname);

            $_rules = "trim|xss_clean|required|{$cpassword_min_length_rule}[{$min_password_length}]|{$cpassword_max_length_rule}[{$max_password_length}]|{$cpassword_matches_rule}[password]";
			$this->form_validation->set_rules('cpassword', lang('Confirm Password'), $_rules);
		}

		if( in_array('email',$enable_form_validation_under_registration)
		) {
			$rulesStr =  'trim|xss_clean|valid_email|callback_check_email';
			if ($this->registration_setting->isRegistrationFieldRequired('Email') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required|valid_email|callback_check_email';
			}
			$this->form_validation->set_rules('email', lang('Email Address'), 'trim|xss_clean|valid_email|callback_check_email');
		}


		# NOTE: Currently, only using CNY
		# $this->form_validation->set_rules('currency', 'Currency', 'trim|xss_clean|required');

		# NOTE: Please arrange by registrationFieldId

		if( in_array('firstName',$enable_form_validation_under_registration)
		) {
			$rulesStr = 'trim|xss_clean';
			# regId.1
			if ($this->registration_setting->isRegistrationFieldRequired('First Name') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('firstName', lang('First Name'), $rulesStr);
		}


		if( in_array('lastName',$enable_form_validation_under_registration)
		) {
			$rulesStr = 'trim|xss_clean';
			# regId.2
			if ($this->registration_setting->isRegistrationFieldRequired('Last Name') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('lastName', lang('Last Name'), $rulesStr);
		}


		if( in_array('birthdate',$enable_form_validation_under_registration)
			&& in_array('terms',$enable_form_validation_under_registration)
		) {

            $this->utils->debug_log('1194.verify_formate_only',$verify_formate_only, 'isRegistrationFieldRequired:', $this->registration_setting->isRegistrationFieldRequired('At Least 18 Yrs. Old and Accept Terms and Conditions'));

			# regId.31
			if ($this->registration_setting->isRegistrationFieldRequired('At Least 18 Yrs. Old and Accept Terms and Conditions') && !$verify_formate_only) {
				if ($this->utils->getConfig('kg_privacy_policy')){
					$age_check_terms 		   = isset($_POST['terms']) ? true : false;
					$policy_policy_check_terms = isset($_POST['policy_policy_check_terms']) ? true : false;
					$civs_check_terms 		   = isset($_POST['civs_check_terms']) ? true : false;
					$this->utils->debug_log('========check_all_terms_in_post',$age_check_terms, $policy_policy_check_terms, $civs_check_terms );
					if($age_check_terms && $policy_policy_check_terms && $civs_check_terms){
						$check_all_terms = true;
					}else{
						$check_all_terms = false;
					}
					$this->utils->debug_log('========check_all_terms',$check_all_terms);
					$this->form_validation->set_rules('terms', lang('xpj.footer_bar.links.terms'), 'trim|xss_clean|callback_check_terms['.$check_all_terms.']');
				}else{
					$this->form_validation->set_rules('terms', lang('xpj.footer_bar.links.terms'), 'trim|xss_clean|callback_check_term');
				}
				# regId.3
				$this->form_validation->set_rules('birthdate', lang('reg.fields.birthdate'), 'trim|xss_clean|callback_check_age['.$verify_formate_only.']');

			} else {
				$this->form_validation->set_rules('terms', lang('xpj.footer_bar.links.terms'), 'trim|xss_clean');
				# regId.3
				$this->form_validation->set_rules('birthdate', lang('reg.fields.birthdate'), 'trim|xss_clean|callback_check_age['.$verify_formate_only.']');

			}
		}

		if( in_array('birthdate',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# check birthdate format
			if ($this->registration_setting->isRegistrationFieldRequired('Birthday') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required|callback_check_birthdate';
			}
			$this->form_validation->set_rules('birthdate', lang('reg.fields.birthdate'), $rulesStr);
		}

		if( in_array('gender',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.4
			if ($this->registration_setting->isRegistrationFieldRequired('Gender') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('gender', lang('reg.fields.gender'), $rulesStr);
		}


		if( in_array('citizenship',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean|callback_check_country';
			# regId.5
			if ($this->registration_setting->isRegistrationFieldRequired('Nationality') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required|callback_check_country';
			}
			$this->form_validation->set_rules('citizenship', lang('reg.fields.citizenship'), $rulesStr);
		}


		if( in_array('birthplace',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.6
			if ($this->registration_setting->isRegistrationFieldRequired('BirthPlace') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('birthplace', lang('reg.fields.birthplace'), $rulesStr);
		}

		if( in_array('language',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.7
			if ($this->registration_setting->isRegistrationFieldRequired('Language') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('language', lang('reg.fields.language'), $rulesStr);
		}


		$playerValidate = $this->utils->getConfig('player_validator');
		$contactRule = isset($playerValidate['contact_number']) ? $playerValidate['contact_number'] : [];
		$contactMin  = isset($contactRule['min']) ? $contactRule['min'] : "";
		$contactMax  = isset($contactRule['max']) ? $contactRule['max'] : "";
		$contactLenRule = "";
		if (isset($contactMin, $contactMax) && $contactMin == $contactMax) {

            $is_contact_number_exact_length_exist = false; // OGP-32860
            if(lang('formvalidation.contact_number.exact_length') != 'formvalidation.contact_number.exact_length'){
                $is_contact_number_exact_length_exist = true;
            }
            // set_message() for contact_number_exact_length of contact_number
            if($is_contact_number_exact_length_exist){
                $contactLenRule .= "|contact_number_exact_length[{$contactMin}]";
                $this->form_validation->set_message('contact_number_exact_length', lang('formvalidation.contact_number.exact_length'));
            }else{
                $contactLenRule .= "|exact_length[{$contactMin}]";
            }
		} else {
			if (is_int($contactMin)) {
				$contactLenRule .= "|min_length[$contactMin]";
			}
			if (is_int($contactMax)) {
				$contactLenRule .= "|max_length[$contactMax]";
			}
		}
        $is_contact_number_begin_on_nonzero = $this->utils->getConfig('is_contact_number_begin_on_nonzero'); // OGP-32860
        if($is_contact_number_begin_on_nonzero){
            $contactLenRule .= "|is_begin_on_nonzero";
            $this->form_validation->set_message('is_begin_on_nonzero', lang('formvalidation.contact_number.is_begin_on_nonzero'));
        }

        if( strpos($contactLenRule,'|is_begin_on_nonzero') !== false
            && strpos($contactLenRule,'|contact_number_exact_length') !== false
        ){
            /// both_ison_and_cnel = both_is_begin_on_nonzero_and_contact_number_exact_length
            // ison = is_begin_on_nonzero
            // cnel = contact_number_exact_length
            // both_ison_and_cnel
            $contactLenRule = "|both_ison_and_cnel[{$contactMin}]". $contactLenRule; // for higher priority
            $this->form_validation->set_message('both_ison_and_cnel', lang('formvalidation.contact_number.both_ison_and_cnel'));
        }

		if( in_array('contactNumber',$enable_form_validation_under_registration)
		){
			# regId.8
			if ($this->registration_setting->isRegistrationFieldRequired('Contact Number') && !$verify_formate_only) {
				if ($this->utils->isEnabledFeature('allow_player_same_number')) {
					$this->form_validation->set_rules('contactNumber', lang('reg.fields.contactNumber'), 'trim|xss_clean' . $contactLenRule . '|required');
				} else {
					$this->form_validation->set_rules('contactNumber', lang('reg.fields.contactNumber'), 'trim|xss_clean' . $contactLenRule . '|required|callback_check_contact');
				}
			} else {
				if ($this->utils->isEnabledFeature('allow_player_same_number')) {
					$this->form_validation->set_rules('contactNumber', lang('reg.fields.contactNumber'), 'trim|xss_clean'. $contactLenRule);
				} else {
					$this->form_validation->set_rules('contactNumber', lang('reg.fields.contactNumber'), 'trim|xss_clean' . $contactLenRule . '|callback_check_contact');
				}
			}
		}

		if( in_array('imAccount',$enable_form_validation_under_registration)
			&& in_array('im_type',$enable_form_validation_under_registration)
		){
			# regId.9
			if ($this->registration_setting->isRegistrationFieldRequired('Instant Message 1') && !$verify_formate_only) {
				$this->form_validation->set_rules('imAccount', lang('reg.fields.imAccount'), 'trim|xss_clean|required|callback_differs_im[imAccount]');
				// $this->form_validation->set_rules('im_type', 'IM 1', 'trim|xss_clean|required|callback_check_im[imAccount]');
			} else {
				$this->form_validation->set_rules('imAccount', lang('reg.fields.imAccount'), 'trim|xss_clean|callback_differs_im[imAccount]');
				$this->form_validation->set_rules('im_type', 'IM 1', 'trim|xss_clean|callback_check_im[imAccount]');
			}
		}


		if( in_array('imAccount2',$enable_form_validation_under_registration)
			&& in_array('im_type2',$enable_form_validation_under_registration)
		){
			# regId.10
			if ($this->registration_setting->isRegistrationFieldRequired('Instant Message 2') && !$verify_formate_only) {
				$this->form_validation->set_rules('imAccount2', lang('reg.fields.imAccount2'), 'trim|xss_clean|required|callback_differs_im[imAccount2]');
				// $this->form_validation->set_rules('im_type2', 'IM 2', 'trim|xss_clean|required|callback_check_im[imAccount2]');
			} else {
				$this->form_validation->set_rules('imAccount2', lang('reg.fields.imAccount2'), 'trim|xss_clean|callback_differs_im[imAccount2]');
				$this->form_validation->set_rules('im_type2', 'IM 2', 'trim|xss_clean|callback_check_im[imAccount2]');
			}
		}


		if( in_array('residentCountry',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean|callback_check_country';
			if ($this->registration_setting->isRegistrationFieldRequired('residentCountry') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required|callback_check_country';
			}
			$this->form_validation->set_rules('residentCountry', lang('a_reg.33'), $rulesStr);
		}


		if( in_array('imAccount3',$enable_form_validation_under_registration)
			&& in_array('im_type3',$enable_form_validation_under_registration)
		){
			# regId.47
			if ($this->registration_setting->isRegistrationFieldRequired('Instant Message 3') && !$verify_formate_only) {
				$this->form_validation->set_rules('imAccount3', lang('reg.fields.imAccount3'), 'trim|xss_clean|required|callback_differs_im[imAccount3]');
				// $this->form_validation->set_rules('im_type3', 'IM 3', 'trim|xss_clean|required|callback_check_im[imAccount3]');
			} else {
				$this->form_validation->set_rules('imAccount3', lang('reg.fields.imAccount3'), 'trim|xss_clean|callback_differs_im[imAccount3]');
				$this->form_validation->set_rules('im_type3', 'IM 3', 'trim|xss_clean|callback_check_im[imAccount3]');
			}
		}

		if( in_array('imAccount4',$enable_form_validation_under_registration)
			&& in_array('im_type4',$enable_form_validation_under_registration)
		){
			# regId.47
			if ($this->registration_setting->isRegistrationFieldRequired('Instant Message 4') && !$verify_formate_only) {
				$this->form_validation->set_rules('imAccount4', lang('reg.fields.imAccount4'), 'trim|xss_clean|required|callback_differs_im[imAccount4]');
				// $this->form_validation->set_rules('im_type3', 'IM 3', 'trim|xss_clean|required|callback_check_im[imAccount4]');
			} else {
				$this->form_validation->set_rules('imAccount4', lang('reg.fields.imAccount4'), 'trim|xss_clean|callback_differs_im[imAccount4]');
				$this->form_validation->set_rules('im_type4', 'IM 4', 'trim|xss_clean|callback_check_im[imAccount4]');
			}
		}

		if( in_array('imAccount5',$enable_form_validation_under_registration)
			&& in_array('im_type5',$enable_form_validation_under_registration)
		){

			if ($this->registration_setting->isRegistrationFieldRequired('Instant Message 5') && !$verify_formate_only) {
				$this->form_validation->set_rules('imAccount5', lang('reg.fields.imAccount5'), 'trim|xss_clean|required|callback_differs_im[imAccount5]');

			} else {
				$this->form_validation->set_rules('imAccount5', lang('reg.fields.imAccount5'), 'trim|xss_clean|callback_differs_im[imAccount5]');
				$this->form_validation->set_rules('im_type5', 'IM 5', 'trim|xss_clean|callback_check_im[imAccount5]');
			}
		}

		if( in_array('secretQuestion',$enable_form_validation_under_registration)
			&& in_array('secretAnswer',$enable_form_validation_under_registration)
		){
			# regId.11
			if ($this->registration_setting->isRegistrationFieldRequired('Security Question') && !$verify_formate_only) {
				$this->form_validation->set_rules('secretQuestion', lang('Security Question'), 'trim|xss_clean|required');
				$this->form_validation->set_rules('secretAnswer', lang('reg.42'), 'trim|xss_clean|required');
			} else {
				$this->form_validation->set_rules('secretQuestion', lang('Security Question'), 'trim|xss_clean');
				$this->form_validation->set_rules('secretAnswer', lang('reg.42'), 'trim|xss_clean');
			}
		}


		if( in_array('invitationCode',$enable_form_validation_under_registration)
		){
			# regId.13
			$this->form_validation->set_rules('invitationCode', lang('Referral code'), 'trim|xss_clean|callback_check_referral');
		}

		if( in_array('tracking_code',$enable_form_validation_under_registration)
		){
			# regId.14{
			if($this->registration_setting->isRegistrationFieldRequired('Affiliate Code') && !$verify_formate_only) {
				if ($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')) {

					$this->form_validation->set_rules('tracking_code', lang('Affiliate code'), 'trim|xss_clean|callback_check_aff_tracking_code|callback_check_aff_tracking_code_is_aff_active|required');
				} else {

					$this->form_validation->set_rules('tracking_code', lang('Affiliate code'), 'trim|xss_clean|required');
				}

			} else {
				if ($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')) {

					$this->form_validation->set_rules('tracking_code', lang('Affiliate code'), 'trim|xss_clean|callback_check_aff_tracking_code|callback_check_aff_tracking_code_is_aff_active');
				} else {

					$this->form_validation->set_rules('tracking_code', lang('Affiliate code'), 'trim|xss_clean');
				}

			}
		}

		if (in_array('affiliate_name', $enable_form_validation_under_registration)){

			if($this->registration_setting->isRegistrationFieldRequired('Affiliate Username') && !$verify_formate_only){

				if ($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')) {
					$this->form_validation->set_rules('affiliate_name', lang('Affiliate Username'), 'trim|xss_clean|required|callback_check_affiliate_name');
				} else {

					$this->form_validation->set_rules('affiliate_name', lang('Affiliate Username'), 'trim|xss_clean|required');
				}

			} else {
				if ($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')) {
					$this->form_validation->set_rules('affiliate_name', lang('Affiliate Username'), 'trim|xss_clean|callback_check_affiliate_name');
				} else {

					$this->form_validation->set_rules('affiliate_name', lang('Affiliate Username'), 'trim|xss_clean');
				}

			}
		}

		if( in_array('withdrawPassword',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.35
			if ($this->registration_setting->isRegistrationFieldRequired('Withdrawal Password') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('withdrawPassword', lang('Withdraw Password'), $rulesStr);
		}



		if( in_array('bankName',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.40
			if ($this->registration_setting->isRegistrationFieldRequired('Bank Name') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('bankName', lang('Bank Name'), $rulesStr);
		}

		if( in_array('bankAccountNumber',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.41
			if ($this->registration_setting->isRegistrationFieldRequired('Bank Account Number') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('bankAccountNumber', lang('a_reg.41'), $rulesStr);
		}


		if( in_array('bankAccountName',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.42
			if ($this->registration_setting->isRegistrationFieldRequired('Bank Account Name') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('bankAccountName', lang('a_reg.42'), $rulesStr);
		}


		if( in_array('region',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean|max_length[120]';
			# regId.37
			if ($this->registration_setting->isRegistrationFieldRequired('Region') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|max_length[120]|required';
			}
			$this->form_validation->set_rules('region', lang('a_reg.37.placeholder'), $rulesStr);
		}

		if( in_array('city',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean|max_length[120]';
			# regId.36
			if ($this->registration_setting->isRegistrationFieldRequired('City') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|max_length[120]|required';
			}
			$this->form_validation->set_rules('city', lang('a_reg.36.placeholder'), $rulesStr);
		}

		if( in_array('address',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean|max_length[120]';
			# regId.43
			if ($this->registration_setting->isRegistrationFieldRequired('Address') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|max_length[120]|required';
			}
			$this->form_validation->set_rules('address', lang('a_reg.43.placeholder'), $rulesStr);
		}


		if( in_array('address2',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean|max_length[120]';
			# regId.44
			if ($this->registration_setting->isRegistrationFieldRequired('Address2') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|max_length[120]|required';
			}
			$this->form_validation->set_rules('address2', lang('a_reg.44.placeholder'), $rulesStr);
		}

		if( in_array('zipcode',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.48
			if ($this->registration_setting->isRegistrationFieldRequired('Zip Code') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('zipcode', lang('a_reg.48'), $rulesStr);
		}


		if( in_array('id_card_number',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.49
			if ($this->registration_setting->isRegistrationFieldRequired('ID Card Number') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('id_card_number', lang('a_reg.49'), $rulesStr);
		}


		if( in_array('dialing_code',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.50
			if ($this->registration_setting->isRegistrationFieldRequired('Dialing Code') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('dialing_code', lang('a_reg.50'), $rulesStr);
		}


		if( in_array('id_card_type',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			# regId.51
			if ($this->registration_setting->isRegistrationFieldRequired('ID Card Type') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('id_card_type', lang('a_reg.51'), $rulesStr);
		}

		if( in_array('sms_verification_code',$enable_form_validation_under_registration)
		){
			#OGP-22751
			if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
				$params = array('contactNumber','sms_api_register_setting');
				$rulesStr = 'trim|xss_clean|callback_check_sms_verification['. json_encode($params) .']';
			}else{
				$rulesStr = 'trim|xss_clean|callback_check_sms_verification[contactNumber]';
			}
			// Skip SMS Verification code if invoked from Api_common - OGP-14163
			if (empty($from_comapi)) {
				if (!$this->utils->getConfig('disabled_sms')) {
					if ($this->registration_setting->isRegistrationFieldRequired('SMS Verification Code') && $this->registration_setting->isRegistrationFieldVisible('Contact Number') && !$verify_formate_only) {
						$this->form_validation->set_rules('sms_verification_code', lang('SMS Verification Code'), $rulesStr.'|required');
					} else {
						$this->form_validation->set_rules('sms_verification_code', lang('SMS Verification Code'), $rulesStr);
					}
				} else {
					$this->form_validation->set_rules('sms_verification_code', lang('SMS Verification Code'), 'trim|xss_clean');
				}
			}
		}


		// if ($this->operatorglobalsettings->getSettingJson('registration_captcha_enabled')) {
        if ($this->utils->getConfig('enabled_registration_captcha') && $this->operatorglobalsettings->getSettingJson('registration_captcha_enabled') && empty($from_comapi)){
			if( in_array('captcha',$enable_form_validation_under_registration)
			){
				$this->form_validation->set_rules('captcha', lang('label.captcha'), 'trim|xss_clean|required|callback_check_captcha');
			}

		}

		if( in_array('newsletter_subscription',$enable_form_validation_under_registration)
		){
			$rulesStr = 'trim|xss_clean';
			if ($this->registration_setting->isRegistrationFieldRequired('Newsletter Subscriptions') && !$verify_formate_only) {
				$rulesStr = 'trim|xss_clean|required';
			}
			$this->form_validation->set_rules('newsletter_subscription', lang('a_reg.52'), $rulesStr);
		}


		//set form validation language
		$this->form_validation->set_message('matches', lang('formvalidation.matches'));

        $fieldname = 'password';
        $_ruleStrDefault = 'not_matches[username]';
        list($_isEnabled, $password_not_matches_rule) = $this->form_validation->apply_rule_arg_by_field($_ruleStrDefault, $fieldname);
        if(! $_isEnabled){ // use default
            $this->form_validation->set_message('not_matches', lang('formvalidation.not_matches'));
        }


		/// moved above "ignore_registration_settings=true".
		// $this->form_validation->set_message('min_length', lang('formvalidation.min_length'));
		// $this->form_validation->set_message('max_length', lang('formvalidation.max_length'));

		$this->form_validation->set_message('required', lang('formvalidation.required'));
		$this->form_validation->set_message('isset', lang('formvalidation.isset'));
		$this->form_validation->set_message('valid_email', lang('formvalidation.valid_email'));
		$this->form_validation->set_message('valid_emails', lang('formvalidation.valid_emails'));
		$this->form_validation->set_message('exact_length', lang('formvalidation.exact_length'));
		$this->form_validation->set_message('alpha', lang('formvalidation.alpha'));
		$this->form_validation->set_message('alpha_numeric', lang('formvalidation.alpha_numeric'));
		$this->form_validation->set_message('alpha_dash', lang('formvalidation.alpha_dash'));
		$this->form_validation->set_message('numeric', lang('formvalidation.numeric'));
		$this->form_validation->set_message('is_numeric', lang('formvalidation.is_numeric'));
		$this->form_validation->set_message('regex_match', lang('formvalidation.regex_match'));
		$this->form_validation->set_message('is_unique', lang('formvalidation.is_unique'));
		$this->form_validation->set_message('less_than', lang('formvalidation.is_unique'));
		$this->form_validation->set_message('greater_than', lang('formvalidation.is_unique'));

		$result = $this->form_validation->run();

		return $result;
	} // EOF validate_registration

	public function validateSmsVerifiedStatus($from_comapi = false, $ignore_registration_settings = false ){

		$this->load->library(array('form_validation','session'));
		$this->load->model(array('operatorglobalsettings', 'registration_setting','sms_verification'));

		$enable_form_validation_under_registration = $this->utils->getConfig('enable_form_validation_under_registration');
		if( in_array('sms_verification_code',$enable_form_validation_under_registration)){
			// Skip SMS Verification code if invoked from Api_common - OGP-14163
			if (empty($from_comapi)) {
				if (!$this->utils->getConfig('disabled_sms')) {
					if ($this->registration_setting->isRegistrationFieldVisible('SMS Verification Code') && $this->registration_setting->isRegistrationFieldVisible('Contact Number')) {

						$playerId = $this->authentication->getPlayerId();
						$session_id = $this->session->userdata('session_id');
						$contact_number = $this->input->post('contactNumber');
						$sms_verification_code = $this->input->post('sms_verification_code');

						if($this->config->item('allow_first_number_zero') && substr($contact_number,0,1) == '0'){
							$contact_number = substr($contact_number,1);
				        }

				        if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
							$usage = sms_verification::USAGE_SMSAPI_REGISTER;
						}else{
							$usage = sms_verification::USAGE_DEFAULT;
						}

						$smsValidateResult = $this->sms_verification->validateSmsVerifiedStatus($playerId, $session_id, $contact_number, $sms_verification_code, $usage);

						$this->utils->debug_log('------------validateSmsVerifiedStatus smsValidateResult',$smsValidateResult);
						return $smsValidateResult;
					}
				}
			}
		}
	}

	/**
	 * overview : check username
	 *
	 * @param string	$username
	 * @return bool
	 */
	public function check_username($username) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        // username_check_username : 2/2
        $fieldname = 'username';
        $_ruleStrDefault = 'check_username';
        $_langKey = ''; // for collect
        list($_isEnabled, $_rule) = $this->form_validation->apply_rule_by_field($_ruleStrDefault, $fieldname, $_langKey);

		# TODO: MOVE TO CONFIG OR OPERATOR SETTINGS OR REGISTRATION SETTINGS?
		$forbidden_names = array('admin', 'moderator', 'hoster', 'administrator', 'mod');
		if (in_array($username, $forbidden_names)) {

            if($_isEnabled){
                $_lang = sprintf(lang($_langKey), $username);
            }else{
                $_langKey = 'notify.4';
                $_lang = "<b>" . $username . "</b> " . lang($_langKey);
            }
			$this->form_validation->set_message('check_username', $_lang);
			return false;
		}
		$this->load->model(['player_model']);
		$result = !$this->player_model->checkUsernameExist($username);
		if (!$result) {

            if($_isEnabled){
                $_lang = sprintf(lang($_langKey), $username);
            }else{
                $_langKey = 'notify.3';
                $_lang = "<b>" . $username . "</b> " . lang($_langKey);
            }
            $this->form_validation->set_message('check_username', $_lang);
		}
		return $result;
	}

	/**
	 * overview : check email
	 *
	 * @param string	$email
	 * @return bool
	 */
	public function check_email($email) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		$this->load->model(['player_model']);
		$result = empty($email) ? : !$this->player_model->checkEmailExist($email);
		if (!$result) {
			$this->form_validation->set_message('check_email', "" . $email . " " . lang('notify.5'));
		}
		return $result;
	}

	/**
	 * overview : check contact
	 *
	 * @param $contact_number
	 * @return bool
	 */
	public function check_contact($contact_number) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        $this->load->model(['player_model']);
        $playerId = $this->authentication->getPlayerId();
        $origin = $this->player_model->getPlayerInfoById($playerId);
        $diff_contactNumber = ($contact_number !== $origin['contactNumber']);

		if (!empty($contact_number) && $diff_contactNumber) {
			$result = !$this->player_model->checkContactExist($contact_number);
			if (!$result) {
				$this->form_validation->set_message('check_contact', lang('The contact number has been used'));
			}
			return $result;
		} else {
			return true;
		}
	}

	/**
	 * overview : check contact
	 *
	 * @param $pix_number
	 * @return bool
	 */
	public function check_cpf_number($pix_number) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        $this->load->model(['player_model']);
        $playerId = $this->authentication->getPlayerId();
        $origin = $this->player_model->getPlayerInfoById($playerId);
        $diff_pixNumber = ($pix_number !== $origin['pix_number']);

		if (!empty($pix_number) && $diff_pixNumber) {
			$result = !$this->player_model->checkCpfNumberExist($pix_number);
			if (!$result) {
				$this->form_validation->set_message('check_cpf_number', lang('CPF number has been used'));
			}
			return $result;
		} else {
			return true;
		}
	}

	/**
	* overview : check ImAccount Exist
	*
	* @param $currentValue imAccount Value
	* @param $field = ['currentField','compareField','is_mobile']
	* @return bool
	*/
	public function checkImAccountExist($currentValue, $field) {

		if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		$this->load->model(['player_model']);
		$this->load->model(array('registration_setting'));

        $playerId = $this->authentication->getPlayerId();
		$origin = $this->player_model->getPlayerInfoById($playerId);

		$field = explode(',', $field);
		$currentField = $field[0];
		$compareField = $field[1];
		$is_mobile = $field[2];

		$currentField = ($currentField == '1') ? '' : $currentField;
		$compareField = ($compareField == '1') ? '' : $compareField;
		$compareValue = $is_mobile ? $origin['imAccount'.$compareField] : $this->input->post('im_account'.$compareField);

		// check currentValue is change or not, if change should verify imaccount.
		$diff_currentValue = ($currentValue !== $origin['imAccount'.$currentField]);

		// 0. Rule: if imAccount or imAccount2 is empty, imAccount4 or imAccount5 can not be filled.
		if (($field[0] == '4' || $field[0] == '5') && $this->registration_setting->checkAccountInfoFieldAllowVisible('imAccount'.$compareField) && $diff_currentValue) {
			if (empty($compareValue) && !empty($currentValue)) {
				$this->form_validation->set_message('checkImAccountExist', 'please fill '.lang('Instant Message '.$field[1]).' first.');
				return false;
			}
		}

		// 1. check currentValue not equal to compareValue. (ex:imAccount1 can not be the same as imAccount4.)
		if (isset($currentValue,$compareValue) && $compareValue == $currentValue) {
			$this->form_validation->set_message('checkImAccountExist', lang('Instant Message '.$field[0]).lang(' can not be the same as '). lang('Instant Message '.$field[1]));
			return false;
		}

		// 2. check imaccount is unique. (ex:imAccount1 must unique in all imAccount1 and imAccount4 field.)
		if (!empty($currentValue) && $diff_currentValue) {
			$result = !$this->player_model->checkImAccountExist($currentValue, $currentField, $compareField);
			if (!$result) {
				$this->form_validation->set_message('checkImAccountExist', lang('Instant Message '.$field[0]).lang(' has been used.'));
			}
			return $result;
		}

		return true;
	}

	/**
	 * overview : check country
	 *
	 * @param $country
	 * @return bool
	 */
	public function check_country($country) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		if (!empty($country)) {
			$result = in_array($country,$this->utils->getCountryList());
			if (!$result) {
				$this->form_validation->set_message('check_country',"<b>" . $country . "</b> ".lang('this country is not on the list'));
			}
			return $result;
		} else {
			return true;
		}
	}

	/**
	 * overview : check referral
	 *
	 * @param string 	$referral_code
	 * @return bool
	 */
	public function check_referral($referral_code) {
		if (!empty($referral_code)) {
			$this->load->library('player_functions');
			$result = $this->player_functions->checkIfReferralCodeExist($referral_code);
			if (!$result) {
				$this->form_validation->set_message('check_referral', "<b>" . $referral_code . "</b> " . lang('notify.6'));
			}
			return !!$result;
		} else {
			return true;
		}
	}

	/**
	 * Check affiliate tracking code at register-time.
	 * @see		validate_registration()
	 * @param	string	$aff_tracking_code
	 * @return	bool	true if code empty or legal; otherwise false.
	 */
	public function check_aff_tracking_code($aff_tracking_code) {
		if (empty($aff_tracking_code)) {
			return true;
		}

		$this->load->model([ 'affiliate' ]);

		// $chkres = $this->affiliate->isAffExistingAndActiveByTrackingCode($aff_tracking_code);
		$chkres = $this->affiliate->checkTrackingCode($aff_tracking_code);

		$this->utils->debug_log(__METHOD__, [ 'aff_tracking_code' => $aff_tracking_code, 'chkres' => $chkres ]);

		if ($chkres == false) {
			$this->utils->clearTrackingCode();
			$this->form_validation->set_message('check_aff_tracking_code', lang('notify.not_valid_tracking_code'));
		}
		return $chkres;

	}

	/**
	 * Check if affiliate tracking code points to an active aff at register-time
	 * @see		validate_registration()
	 * @param	string	$aff_tracking_code
	 * @return	bool	true if code empty or legal; otherwise false.
	 */
	public function check_aff_tracking_code_is_aff_active($aff_tracking_code) {
		$this->utils->debug_log(__METHOD__, [ 'aff_tracking_code' => $aff_tracking_code ]);
		if (empty($aff_tracking_code)) {
			return true;
		}

		$this->load->model([ 'affiliate' ]);

		$chkres = $this->affiliate->isAffExistingAndActiveByTrackingCode($aff_tracking_code);
		// $chkres = $this->affiliate->checkTrackingCode($aff_tracking_code);

		$this->utils->debug_log(__METHOD__, [ 'chkres' => $chkres ]);

		if ($chkres == false) {
			$this->utils->clearTrackingCode();
			$this->form_validation->set_message('check_aff_tracking_code_is_aff_active', lang('notify.aff_invalid_or_inactive'));
		}
		return $chkres;

	}

	public function check_affiliate_name($affiliate_name) {
		if (empty($affiliate_name)) {
			return true;
		}

		$this->load->model([ 'affiliate' ]);

		// $chkres = $this->affiliate->isTrackingCodeValid($aff_tracking_code);
		$chkres = $this->affiliate->isAffExistingAndActiveByUsername($affiliate_name);

		$this->utils->debug_log(__METHOD__, [ 'affiliate_name' => $affiliate_name, 'chkres' => $chkres ]);

		if ($chkres == false) {
			$this->form_validation->set_message('check_affiliate_name', lang('notify.not_valid_affiliate_name'));
		}
		return $chkres;

	}

	public function check_age($birthday, $verify_formate_only = false) {
		return false;
		$this->utils->debug_log('======birthday',$birthday);
		$limitAge = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
		$errMsg = sprintf(lang('mod.mustbeAtLeastLimitAge'),$limitAge).'</br>'.lang('mod.termsAndConditions');
		$term = $this->input->post('terms');
		if(!$term){
			$this->form_validation->set_message('check_age', '');
		} else {
			$this->form_validation->set_message('check_age', $errMsg);
		}
		if(empty($birthday) || strlen($birthday) < 9) {
			if($this->registration_setting->isRegistrationFieldRequired('Birthday') && !$verify_formate_only){
				return false;
			}else {
				return true;
			}
		}else{
			$age = $this->player_functions->get_age($birthday);

			if ($age < $limitAge) {
				return false;
			}else{
				return true;
			}
		}
	}

	public function check_term($is_term_checked) {
		$this->utils->debug_log('========check_term',$is_term_checked);
		$limitAge = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
		$errMsg = sprintf(lang('mod.mustbeAtLeastLimitAge'),$limitAge).'</br>'.lang('mod.termsAndConditions');
		if(!$is_term_checked) {
			$this->form_validation->set_message('check_term', $errMsg);
			return false;
		}
		return true;
	}

	public function check_terms($terms = null, $is_all_terms_checked) {
		$this->utils->debug_log('========check_all_terms_in_callback_function',$is_all_terms_checked);
		$limitAge = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
		$errMsg = sprintf(lang('mod.mustbeAtLeastLimitAge'),$limitAge).'</br>'.lang('mod.termsAndConditions');
		if(!$is_all_terms_checked) {
			$this->form_validation->set_message('check_terms', $errMsg);
			return false;
		}
		return true;
	}

	public function check_birthdate($birthdate) {
		$this->utils->debug_log('-------------------------check_birthdate',$birthdate);
		$errMsg = lang('mod.birthdateformat');
		if(!empty($birthdate)){
			$tempDate = explode('-', $birthdate);
			if (checkdate($tempDate[1], $tempDate[2], $tempDate[0])) {//checkdate(month, day, year)
				return true;
			}
		}
		$this->form_validation->set_message('check_birthdate', $errMsg);
		return false;
	}

	/**
	 * overview : check captcha
	 *
	 * @param string	$val
	 * @return bool
	 */
	public function check_captcha($val, $namespace = 'default') {
		$this->utils->debug_log('check_captcha', $val, $this->session->all_userdata());

		$this->load->model('operatorglobalsettings');
		$rlt = true;
		if ($this->operatorglobalsettings->getSettingJson('registration_captcha_enabled') || $this->operatorglobalsettings->getSettingJson('login_captcha_enabled')) {

			if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha' && $namespace != 'static_site'){
				$config['call_socks5_proxy'] = $this->config->item('call_socks5_proxy');
				$config['timeout_second']    = $this->utils->getConfig('enabled_captcha_of_3rdparty')['hcaptcha_timeout_seconds'];
				$config['connect_timeout']   = $this->utils->getConfig('enabled_captcha_of_3rdparty')['hcaptcha_timeout_seconds'];
				$config['is_post'] 			 = TRUE;
				$params['secret'] = $this->utils->getConfig('enabled_captcha_of_3rdparty')['secret'];
				$params['response'] = $val;
		        $response_result = $this->utils->httpCall('https://hcaptcha.com/siteverify', $params, $config);
		        $json_result = json_decode($response_result[1],true);
		        $this->utils->debug_log(__METHOD__,'========register validationHcaptchaToken', $json_result);

		        if($json_result['success']){
		        	return true;
		        }else{
					$rlt = false;
		        }
			}else{
				$active = $this->config->item('si_active');
				$current_host = $this->utils->getHttpHost();
				$active_domain_assignment = $this->config->item('si_active_domain_assignment');
				if( ! empty($active_domain_assignment[$current_host]) ){
					$active = $active_domain_assignment[$current_host];
				}
				$allsettings = array_merge($this->config->item('si_general'), $this->config->item($active), ['namespace' => $namespace]);

				$this->load->library('captcha/securimage');
				$securimage = new Securimage($allsettings);
				$postCaptcha=$this->input->post('captcha');
				$this->utils->debug_log('check captcha session',$this->session->userdata('auth_flag'), $postCaptcha);

				$rlt = $securimage->check($postCaptcha);

				$this->utils->debug_log('check_captcha result '.($rlt ? 'true' : 'false'), $rlt, $postCaptcha, $this->session->all_userdata());
			}
			if(!$rlt){
				$this->form_validation->set_message('check_captcha', lang('error.captcha'));
			}
		}
		return $rlt;
	}

	/**
	 * overview : check captcha
	 *
	 * @param string	$val
	 * @return bool
	 */
	public function check_sms_captcha() {

		$this->load->model('operatorglobalsettings');

		$this->load->library('captcha/sms_securimage');

		$securimage = new Sms_securimage();

		$this->utils->debug_log('check captcha session',$this->session->userdata('auth_flag'), $this->session->all_userdata(), $this->input->post('sms_captcha'));

		$rlt = $securimage->check($this->input->post('sms_captcha'));

		$this->utils->debug_log('result', $rlt);

		return $rlt;
	}

	/**
	 * overview : check im
	 *
	 * @param $im_type
	 * @param $field
	 * @return bool
	 */
	public function check_im($im_type, $field) {

		$im_account = $this->input->post($field);

		if (isset($im_account)) {
			switch ($im_type) {
			case 'QQ':
				$this->form_validation->set_message('check_im', lang('formvalidation.numeric'));
				return $this->form_validation->numeric($im_account);

			case 'MSN':
				$this->form_validation->set_message('check_im', lang('valid_email'));
				return $this->form_validation->valid_email($im_account);
			}
		}

		return true;
	}

	/**
	 * callback method for verifying IM account duplication, used in validate_registration
	 * rewritten to support 3 instant messengers (OGP-2594)
	 *
	 * @param	string	$str	default arg provided by CI validator
	 * @param	string	$field	what field is this
	 *                      (NOTE: different with old differs_im())
	 * @see		player_auth_module::validate_registration()
	 * @return	bool	true if validation OK (no duplication); otherwise false
	 */
	public function differs_im($str, $field) {
		$im_acc_fields = [ 'imAccount', 'imAccount2', 'imAccount3', 'imAccount4', 'imAccount5' ];
		$im_acc_vals = [];

		// Collect imAccount* values
		foreach ($im_acc_fields as $key => $im_acc_field) {
			// excluding current $field
			if ($im_acc_field == $field) {
				unset($im_acc_fields[$key]);
				continue;
			}
			$im_acc_vals[$im_acc_field] = $this->input->post($im_acc_field);
		}
		$this->utils->debug_log('DIFFERS_IM', 'im_acc_vals', $im_acc_vals);

		// Match $str against collected imAccount* values
		$dup = false;
		if (empty($str) || count($im_acc_fields) == 0) {
			// If $str empty, assert false directly
		}
		else {
			foreach ($im_acc_vals as $im_acc_field => $im_acc_val) {
				if (empty($im_acc_val)) {
					// Target empty => no need to check
					continue;
				}
				else if ($im_acc_val == $str) {
					$this->form_validation->set_message('differs_im', lang('notify.7'));
					$dup = true;
					break;
				}
			}
		}

		$success = !$dup;
		$this->utils->debug_log('DIFFERS_IM', 'success', $success);
		return $success;
	}

	/**
	 * overview : check sms verification
	 *
	 * @param string	$sms_verification_code
	 * @param $field
	 * @return bool
	 */
	public function check_sms_verification($sms_verification_code, $field) {

		$this->load->library('session');
		$this->load->model('sms_verification');

		$usage = null;
		if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
			$params = json_decode($field,true);
			if (is_array($params)  && count($params) > 1) {
				$field = $params[0];
				$usage = $params[1];
			}
		}
		$this->utils->debug_log(__METHOD__,'field usage',$field, $usage, gettype($field));

		$playerId = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$contact_number = $this->input->post($field);
		if($this->config->item('allow_first_number_zero') && substr($contact_number,0,1) == '0'){
        	$contact_number = substr($contact_number,1);
        }
		$result = !isset($sms_verification_code) || $this->sms_verification->validateVerificationCode($playerId, $session_id, $contact_number, $sms_verification_code, $usage);
		if (!$result) {
			$this->form_validation->set_message('check_sms_verification', lang('Invalid SMS verification code'));
		}

		return $result;
	}

	/**
	 * overview : post registere player
	 */
	public function postRegisterPlayer() {
        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		$this->output->set_header('Access-Control-Allow-Origin:*');
		$this->output->set_header("Access-Control-Allow-Headers: X-Requested-With");
		$this->utils->debug_log('POSTREGISTERPLAYER post', $this->utils->filterPasswordLogs($this->input->post()));


        if($this->authentication->isLoggedIn()){
            return $this->goPlayerHome();
        }

		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($this->input->post())) {
		    return $this->returnJsonResult(false);
		}

		if (static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'register')) {
            $username = $this->input->post('username');
            $this->utils->debug_log('block login on api login', $username, $this->utils->tryGetRealIPWithoutWhiteIP());
			
            return show_error('Reached limit, try later', 403);
        }

        //check ip limit
        $ip=$this->utils->getIP();
        $type='register';
        $this->load->model(['player_model']);
        $err=null;
        $reached=$this->player_model->reachedIpLimitHourlyBy($ip, $type, $err);
        if($reached===null){
            return show_error($err, 500);
        }
        if($reached===true){
            $this->utils->error_log('reached ip limit, blocked', $ip, $type);
            //block
            return show_error('Reached limit, try later', 403);
        }

		# LIBRARIES
		$this->load->library(array('session', 'sms/sms_sender'));
		$this->load->model(array('group_level', 'agency_model','communication_preference_model', 'gbg_logs_model', 'email_template_model', 'acuris_logs_model', 'duplicate_contactnumber_model'));


		$tracking_code = $this->input->post('tracking_code');
		if(empty($tracking_code)){
			$tracking_code=$this->getTrackingCode();
		}
		$validate_code = true;
		if($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')){
			$validate_code = $this->check_aff_tracking_code_is_aff_active($tracking_code);
		}
		if($validate_code) {
			$tracking_source_code = $this->input->post('tracking_source_code');
			if(empty($tracking_source_code)){
				$tracking_source_code=$this->getTrackingSourceCode();
			}
		} else {
			$tracking_code = null;
			$tracking_source_code = null;
		}

		$agent_tracking_code = $this->input->post('agent_tracking_code');
		if(empty($agent_tracking_code)){
			$agent_tracking_code=$this->getAgentTrackingCode();
		}
		$agent_tracking_source_code = $this->input->post('agent_tracking_source_code');
		if(empty($agent_tracking_source_code)){
			$agent_tracking_source_code=$this->getAgentTrackingSourceCode();
		}

        # third party login
        $ignore_registration_settings = false;
        $thirdPartyLoginType = $this->input->post('thirdPartyLoginType');
        if(!empty($thirdPartyLoginType)){
            $this->load->model('third_party_login');
            switch ($thirdPartyLoginType) {
                case Third_party_login::THIRD_PARTY_LOGIN_TYPE_LINE:
                    $line_user_id = $this->input->post('line_user_id');
                    $line_player  = $this->third_party_login->getLinePlayersByUserId($line_user_id);
                    if(!empty($line_player)){
						if ( ! empty($this->utils->getConfig('enable_OGP19860')) ){
							$ignore_registration_settings = false;
						}else{
							$ignore_registration_settings = true;
						}
                    }
                    break;
				case Third_party_login::THIRD_PARTY_LOGIN_TYPE_FACEBOOK:
					$facebook_user_id = $this->input->post('facebook_user_id');
                    $facebook_player  = $this->third_party_login->getFacebookPlayersByUserId($facebook_user_id);
                    if (!empty($facebook_player)) {
                        if (! empty($this->utils->getConfig('enable_OGP19860'))) {
                            $ignore_registration_settings = false;
                        } else {
                            $ignore_registration_settings = true;
                        }
                    }
					break;
				case Third_party_login::THIRD_PARTY_LOGIN_TYPE_GOOGLE:
					$google_user_id = $this->input->post('google_user_id');
                    $google_player  = $this->third_party_login->getGooglePlayersByUserId($google_user_id);
                    if (!empty($google_player)) {
                        if (! empty($this->utils->getConfig('enable_OGP19860'))) {
                            $ignore_registration_settings = false;
                        } else {
                            $ignore_registration_settings = true;
                        }
                    }
                    break;
				case Third_party_login::THIRD_PAETY_LOGIN_TYPE_OLE:
					$ole_user_id = $this->input->post('ole_user_id');
					$ole_player = $this->third_party_login->getPlayerByOleId($ole_user_id);
					if (!empty($ole_player)) {
                        if (! empty($this->utils->getConfig('enable_OGP19860'))) {
                            $ignore_registration_settings = false;
                        } else {
                            $ignore_registration_settings = true;
                        }
                    }
                    break;
                default:
                    break;
            }
        }

		$from_comapi = false; // for switch sms_verification_code.

		# VALIDATE
		$validation_result = $this->validate_registration($from_comapi, $ignore_registration_settings);

        if ($this->utils->isEnabledFeature('enable_username_cross_site_checking')) {
            $username = $this->input->post('username', true);
            if ($this->player_model->checkCrossSiteByUsername($username, true)) {
                return $this->iframe_register($tracking_code, $tracking_source_code, $agent_tracking_code, $agent_tracking_source_code);
            }
        }

        if (!$validation_result) {
			$message = validation_errors();
			$this->utils->debug_log('POSTREGISTERPLAYER error', $message);

			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

			#OGP-21403
			$checkAndUpdateSmsVerified = $this->validateSmsVerifiedStatus($from_comapi, $ignore_registration_settings);
			$this->utils->debug_log('------------validateSmsVerifiedStatus result',$checkAndUpdateSmsVerified);

			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}

			if( empty( $message ) ) $this->session->unset_userdata('result');

            return $this->iframe_register($tracking_code, $tracking_source_code, $agent_tracking_code, $agent_tracking_source_code);
        }

        if($this->player_model->isReachDailyIPAllowedRegistrationLimit($this->utils->getIp())){
            $message = lang('reg.reach_limit_of_single_ip_registrations_per_day');
            $this->utils->debug_log('POSTREGISTERPLAYER error', $message);

            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

            if ($this->input->is_ajax_request()) {
                $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                return;
            }

            if( empty( $message ) ) $this->session->unset_userdata('result');

            return $this->iframe_register($tracking_code, $tracking_source_code, $agent_tracking_code, $agent_tracking_source_code);
        }

        # REQUEST PARAMETERS
		$username = strtolower($this->input->post('username'));

        $this->utils->debug_log('player_auth_module::postRegisterPlayer()', 'tracking_code', $tracking_code, 'tracking_source_code', $tracking_source_code,
            'agent_tracking_code', $agent_tracking_code, 'agent_tracking_source_code', $agent_tracking_source_code);

        $prefixOfPlayer=$this->getPrefixUsernameOfPlayer($tracking_code);
        //add to username
        if(!empty($prefixOfPlayer)){
            $username=$prefixOfPlayer.$username;
        }

        #OGP-18537 if birthdate not require need validate format
        #check_birthdate
        $birthday = $this->input->post('birthdate');
        if(!empty($birthday)){
        	if(!$this->check_birthdate($birthday)){
        		$this->returnJsonResult(array('status' => 'error', 'msg' => lang('mod.birthdateformat')));
                return;
        	}
        }

        $password = $this->input->post('password');
        $email = $this->input->post('email');
        $contact_number = $this->input->post('contactNumber');
        $im_type = $this->input->post('im_type');
        $im_type2 = $this->input->post('im_type2');
        $im_account = $this->input->post('imAccount');
        $im_account2 = $this->input->post('imAccount2');
        $im_account3 = $this->input->post('imAccount3');
        $im_account4 = $this->input->post('imAccount4');
		$im_account5 = $this->input->post('imAccount5');
        $referral_code = $this->input->post('invitationCode');
        $sms_verification_code = $this->input->post('sms_verification_code');
        $levels_options = $this->input->post('levels_options');
        $withdraw_password = $this->input->post('withdrawPassword');
		$dialing_code = $this->input->post('dialing_code');

        // bank info
        $bank_name = $this->input->post('bankName');
        $bank_account_num = $this->input->post('bankAccountNumber');
        $bank_account_name = $this->input->post('firstName') . ' ' .$this->input->post('lastName');

        //add tracking banner
        $visit_record_id = $this->session->userdata('visit_record_id');

        $httpHeadrInfo = $this->session->userdata('httpHeaderInfo') ?: $this->utils->getHttpOnRequest();
        $header_referrer = preg_replace('/\s+/', '', $httpHeadrInfo['referrer']);
        $referrer = $header_referrer ?: $_SERVER['HTTP_REFERER'];

        // -- Process Communication Preference
        $config_prefs = $this->utils->getConfig('communication_preferences');
        $communication_preference = null;

        if($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($config_prefs))
            $communication_preference = $this->communication_preference_model->getCommunicationPreferenceChanges($this->input->post());
        if($this->config->item('allow_first_number_zero') && substr($contact_number,0,1) == '0'){
        	$contact_number = substr($contact_number,1);
        }

		$this->load->model(['affiliatemodel']);
		$theAffiliat = $this->affiliatemodel->getAffByTrackingCode($tracking_code);
		$disable_cashback_on_registering = isset($theAffiliat['disable_cashback_on_registering']) ? $theAffiliat['disable_cashback_on_registering'] : 0;
		$disable_promotion_on_registering = isset($theAffiliat['disable_promotion_on_registering']) ? $theAffiliat['disable_promotion_on_registering'] : 0;
		$this->utils->debug_log('postRegisterPlayerTheAffiliat', $theAffiliat, '$tracking_code:', $tracking_code, '$tracking_source_code:', $tracking_source_code);

        $player_data = array(
            # Player
            'username' => $username,
            'gameName' => $username,
            'email' => $email,
            'password' => $password,
            'secretQuestion' => $this->input->post('secretQuestion'),
            'secretAnswer' => str_replace('%20', ' ', $this->input->post('secretAnswer')),
            'verify' => $this->player_functions->getRandomVerificationCode(),
            'withdraw_password' => $withdraw_password,


            # Player Details
            'firstName' => $this->input->post('firstName'),
            'lastName' => $this->input->post('lastName'),
            'language' => $this->input->post('language'),
            'gender' => $this->input->post('gender'),
            'birthdate' => $birthday,
            'contactNumber' => $contact_number,
            'citizenship' => $this->input->post('citizenship'),
            'imAccount' => $this->input->post('imAccount'),
            'imAccountType' => $this->input->post('im_type'),
            'imAccount2' => $this->input->post('imAccount2'),
            'imAccountType2' => $this->input->post('im_type2'),
            'imAccount3' => $this->input->post('imAccount3'),
            'imAccountType3' => $this->input->post('im_type3'),
            'imAccount4' => $this->input->post('imAccount4'),
            'imAccountType4' => $this->input->post('im_type4'),
			'imAccount5' => $this->input->post('imAccount5'),
            'imAccountType5' => $this->input->post('im_type5'),
            'birthplace' => $this->input->post('birthplace'),
            'registrationIp' => $this->utils->getIP(),
            'registrationWebsite' => $referrer,
            'residentCountry' => $this->input->post('residentCountry'),
            'city' => $this->input->post('city'),
            'address' => $this->input->post('address'),
            'address2' => $this->input->post('address2'),
            'address3' => $this->input->post('address3'),
            'zipcode' => $this->input->post('zipcode'),
            'dialing_code' => $this->input->post('dialing_code'),
            'id_card_number' => $this->input->post('id_card_number'),

            // player_preference
            'username_on_register' => $this->input->post('username'),

            # Codes
            'tracking_code' => $tracking_code,
            'tracking_source_code' => $tracking_source_code,
            'agent_tracking_code' => $agent_tracking_code,
            'agent_tracking_source_code' => $agent_tracking_source_code,

            # SMS verification
            'verified_phone' => !empty($sms_verification_code),

			# from affiliates
			'disable_promotion_on_registering' => $disable_promotion_on_registering,
			'disable_cashback_on_registering' => $disable_cashback_on_registering,

            'visit_record_id' => $visit_record_id,
            'newsletter_subscription' => $this->input->post('newsletter_subscription') == 'on' ? 1 : 0,
            'communication_preference' => $communication_preference
		);

        $this->utils->removeReferralCodeCookie();
        $this->utils->removeBtagCookie();

        $refereePlayerId = null;

        if (!empty($referral_code)){
            $refereePlayerId = $this->player_model->getPlayerIdByReferralCode($referral_code);
        }
        if (!empty($refereePlayerId)){
            $player_data['refereePlayerId'] = $refereePlayerId;
            $player_data['referral_code'] = $referral_code;
        }

        if($this->utils->isEnabledFeature('enable_income_access') && $this->input->post('btag')){
            $player_data['btag'] = $this->input->post('btag');
        }

		//OGP-23538 add cehcking if blackbox it not empty if feature is enabled
		$this->CI->load->library(['iovation_lib']);
		$isIovationEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_registration') && $this->CI->iovation_lib->isReady;
		$ioBlackBox = null;
		if($isIovationEnabled){
			$ioBlackBox = $this->input->post('ioBlackBox');
			if(!$this->utils->getConfig('allow_empty_blackbox') && empty($ioBlackBox)){
				$message = lang('notify.127');
				$this->utils->error_log('Error registration missing ioBlackBox', $player_data);
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}

                redirect('/player_center/iframe_register');
			}
		}

        # REGISTER
        $playerId = null;

        if($this->utils->isEnabledFeature('enable_username_cross_site_checking')){
            //global lock
            $add_prefix = false;
            $anyid = 0;
        } else {
            //not global lock
            $add_prefix = true;
            $anyid = random_string('numeric', 5);
        }

		$controller = $this;
		$this->load->model(['wallet_model']);

        $this->lockAndTransForRegistration($anyid, function () use ($controller, $player_data,&$playerId) {
            $playerId = $controller->player_model->register($player_data, false, true, false, false);
            return (!empty($playerId)) ? true : false;
		}, $add_prefix);

        if(!empty($playerId)){
			$trackingevent_source_type = 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM';
			$method = "Commom_register";

            if(!empty($line_player['line_user_id'])){
				$trackingevent_source_type = 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_LINE';
				$method = "Line_register";
                $data['player_id'] = $playerId;
				$this->third_party_login->updateLinePlayersByUserId($line_player['line_user_id'], $data);
				$this->player_model->updatePlayerEmail($playerId, $this->input->post('line_email'));
				$this->player_model->updatePlayerImAccount($playerId, $this->input->post('line_email'));
			}

            if (!empty($facebook_player['facebook_user_id'])) {
				$trackingevent_source_type = 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_FACEBOOK';
				$method = "Facebook_register";
                $data['player_id'] = $playerId;
                $this->third_party_login->updateFacebookPlayersByUserId($facebook_player['facebook_user_id'], $data);
            }

			if (!empty($google_player['google_user_id'])) {
				$trackingevent_source_type = 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_GOOGLE';
				$method = "Google_register";
                $data['player_id'] = $playerId;
                $this->third_party_login->updateGooglePlayersByUserId($google_player['google_user_id'], $data);
            }

			if(!empty($ole_player['ole_user_id'])){
				$trackingevent_source_type = 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_OLE';
				$method = "OLE_register";
				$data['player_id'] = $playerId;
				$this->third_party_login->updatePlayersByPlayerId("ole_auth_player", $ole_player['ole_user_id'], $data);
			}

			$playerInfo = $this->player_model->getPlayerById($playerId);
			$playerContactInfo = $this->player->getPlayerContactInfo($playerId);
			$affiliateOfPlayer = $this->player_model->getAffiliateOfPlayer($playerId);

			$postData = array(
				//clever tap
				"Method" => $method,
				"Status" => "Success",
				"Platforms" => $this->utils->is_mobile() ? "Mobile" : "Desktop",
				"SignupDate" => $playerInfo->createdOn,
				"Line" => $playerContactInfo['imAccount'],
				"PhoneNumber" => $playerContactInfo['contactNumber'],
				//posthog
				'username' => $playerInfo->username,
				'affiliate' => '',
				'channel' => '',
				'gcid' => '',
				'SBE_affiliate' => $affiliateOfPlayer
			);

			$this->utils->playerTrackingEvent($playerId, $trackingevent_source_type, $postData);

			$this->bind_register_proccess($playerId);

			//sync
			$this->syncPlayerCurrentToMDBWithLock($playerId, $username, true);
			// -- Save HTTP Request after lock/transaction
			$this->load->model(['http_request','player_attached_proof_file_model']);
			$this->utils->saveHttpRequest($playerId, Http_request::TYPE_REGISTRATION);

			// -- save default attached file status history
			$this->player_attached_proof_file_model->saveAttachedFileStatusHistory($playerId);

			if ($this->utils->getConfig('enable_player_to_register_with_existing_contactnumber')) {
				$this->saveDuplicateContactNumberHistory($playerId, $contact_number);
			}
		}

        if(empty($playerId)){
            $message = lang('Register failed, try it later');
            $this->utils->debug_log('POSTREGISTERPLAYER error', $message);

            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

            if ($this->input->is_ajax_request()) {
                $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                return;
            }

            if( empty( $message ) ) $this->session->unset_userdata('result');

            return $this->iframe_register($tracking_code, $tracking_source_code, $agent_tracking_code, $agent_tracking_source_code);
        }

        if($this->utils->isEnabledFeature('enable_player_registered_send_msg')) {

            $this->load->model(['cms_model', 'player_model', 'queue_result']);
            $this->load->library(["lib_queue"]);
            $sms_template = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_REGISTERED);

            $searchArr=['{player_username}', '{player_center_url}', '{mobile_number}'];
            $replaceArr=[ $username, $this->utils->getSystemUrl('player'), $contact_number];
            $content = str_replace($searchArr, $replaceArr, $sms_template);
            $mobileNum = !empty($dialing_code)? $dialing_code.'|'.$contact_number : $contact_number;
            $use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
			$useSmsApi = null;
			$sms_setting_msg = '';
			if ($use_new_sms_api_setting) {
			#restrictArea = action type
				$sessionId = $this->session->userdata('session_id');
				$restrictArea = 'sms_api_manager_setting';
				list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($playerId, $mobileNum, $restrictArea, $sessionId);
			}

			$this->utils->debug_log(__METHOD__, 'use new sms api',$useSmsApi, $sms_setting_msg);

            if ($this->utils->isEnabledFeature('enabled_send_sms_use_queue_server')) {
                $isVerifiedPhone = $this->player_model->isVerifiedPhone($playerId);
                if ($content && $isVerifiedPhone) {
                    $callerType = Queue_result::CALLER_TYPE_PLAYER;
                    $caller = $playerId;
                    $state = null;

                    $this->lib_queue->addRemoteSMSJob($mobileNum, $content, $callerType, $caller, $state);
                }
            } else {
                $this->sms_sender->send($mobileNum, $content, $useSmsApi);
            }
        }

        if(!empty($bank_account_num) && !empty($bank_account_name)) {
            $banks = array( 0 => 'Deposit', 1 => 'Withdraw' );
            foreach( $banks as $dwBank => $bank) {
                $data = array(
                    'playerId' => $playerId,
                    'bankTypeId' => $bank_name,
                    'bankAccountNumber' => $bank_account_num,
                    'bankAccountFullName' => $bank_account_name,
                    'bankAddress' => '',
                    'city' => '',
                    'province' => '',
                    'branch' => '',
                    'isRemember' => 1, // 0 is false
                    'dwBank' => "$dwBank", // 1 withdraw , 0 deposit
                    'status' => '0', // 0 is active
                );
                $this->player_functions->addBankDetailsByWithdrawal($data);
            }
        }

        if ($this->operatorglobalsettings->getSettingJson('login_after_registration_enabled')) {

            # LOGIN
            $this->authentication->login($this->input->post('username'), $password); // for Case Sensitive
            # MARK AS NEW USER
            $this->session->set_userdata('new_user', true);

            # PROCESS PROMO IF THERE'S ANY (CODE DOES NOT MAKE SENSE)
            if (!empty($this->session->userdata('promoCode')) && $this->session->userdata('promoCode') == 'depAFae') {
                $message = lang('notify.8'); # UNUSED VARIABLE
            }

        }

        $this->utils->clearTrackingCode();
        $this->utils->clearAgentTrackingCodeFromSession();
        $this->utils->clearAgentTrackingSourceCodeFromSession();

		$this->utils->clearTrackingToken();

        $this->session->unset_userdata('visit_record_id');
        $this->session->unset_userdata('httpHeaderInfo');

        // SEND MESSAGE TO PLAYER EMAIL FOR ACCOUNT VERIFICATION
        $email_sent_token = null;
        if (!empty($email)) {
			#sending email
			$this->load->library(['email_manager']);
	        $template = $this->email_manager->template('player', 'player_verify_email', array('player_id' => $playerId));
	        $template_enabled = $template->getIsEnableByTemplateName(true);
	        if($template_enabled['enable']){
	        	$email = $this->player->getPlayerById($playerId)['email'];
	        	$email_sent_token = $template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $playerId);
	        }
        }


        if ($levels_options){ // if select level options
            $this->group_level->startTrans();
            $this->group_level->adjustPlayerLevel($playerId, $levels_options);
            $this->group_level->endTrans();
        }

        if ($this->input->is_ajax_request()) {
            $this->returnJsonResult(array('status' => 'success', 'msg' => lang('notify.9')));
            return;
        }

        if (!$this->utils->isEnabledFeature('enable_registered_show_success_popup') && $this->operatorglobalsettings->getSettingJson('redirect_after_registration') != 2) {
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('notify.9'));
        }

        if ($this->utils->isEnabledFeature('enable_pep_gbg_api_authentication') && $this->operatorglobalsettings->getSettingJson('generate_pep_gbg_auth_after_registration_enabled') && $this->utils->isEnabledFeature('show_pep_authentication')) {
            $gbg_response = $this->gbg_logs_model->generate_gbg_authentication($playerId);
            $this->utils->debug_log('PEP Authentication response', $gbg_response);
        }

        if ($this->utils->isEnabledFeature('enable_c6_acuris_api_authentication') && $this->operatorglobalsettings->getSettingJson('generate_c6_acuris_auth_after_registration_enabled') && $this->utils->isEnabledFeature('show_c6_authentication')) {
            $gbg_response = $this->acuris_logs_model->generate_acuris_authentication($playerId);
            $this->utils->debug_log('C6 Authentication response', $gbg_response);
        }

        $this->utils->debug_log('============================triggerRegisterEvent');
		$this->triggerRegisterEvent($playerId);

		if($isIovationEnabled){
			$this->utils->debug_log('============================triggerRegisterToIovation');
			$iovationparams = [
				'player_id'=>$playerId,
				'ip'=>$this->utils->getIP(),
				'blackbox'=>$ioBlackBox,
			];
        	$iovationResponse = $this->CI->iovation_lib->registerToIovation($iovationparams);
			$this->utils->debug_log('Post registration Iovation response', $iovationResponse);

			//check if auto block and to block based on the result
			$isAutoBlockEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_auto_block_player_if_denied');
			if($isAutoBlockEnabled && isset($iovationResponse['iovation_result']) && $iovationResponse['iovation_result']=='D'){
				$this->player_model->blockPlayerWithoutGame($playerId);
				$this->authentication->logout();
				$message = lang('notify.122');
				$this->utils->debug_log('Post registration Iovation response error', $iovationResponse);

				// OGP-21074 add tag if iovation denied during registration
				$adminUserId = Transactions::ADMIN;
				$tagName = 'Iovation Denied - Registration';
				$tagId = $this->player_model->getTagIdByTagName($tagName);
				if(empty($tagId)){
					$tagId = $this->player_model->createNewTags($tagName,$adminUserId);
				}
				$this->player_model->addTagToPlayer($playerId,$tagId,$adminUserId);

				$this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, $message);

				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}

				if( empty( $message ) ) $this->session->unset_userdata('result');

				//redirect('player_center/iframe_register');
				return $this->iframe_register($tracking_code, $tracking_source_code, $agent_tracking_code, $agent_tracking_source_code);
			}
        }

        if($this->utils->getConfig('enable_fast_track_integration')) {
            $this->load->library('fast_track');
            $this->fast_track->register($playerId);
        }

        $promocms_ids = $this->utils->getConfig('registered_player_auto_apply_promocms_ids');
		if(!empty($promocms_ids)){
			$this->load->model(['promorules']);
			$this->promorules->applyPromoFromRegistration($promocms_ids, $playerId, $player_data['registrationIp']);
		}

        if (!$this->operatorglobalsettings->getSettingJson('login_after_registration_enabled')) {
// $this->utils->debug_log('OGP-19808.1956.goPlayerLogin');
            $this->goPlayerLogin();
            return;
        }
// $this->utils->debug_log('OGP-19808.1959.post', $this->input->post());
// $this->utils->debug_log('OGP-19808.1959.goto_url', $this->input->post('goto_url'));
        if ($this->input->post('goto_url') ){
			if($this->input->post('from_api')){
                $player_token = $this->common_token->getPlayerToken($playerId);

                $ret = [
                    'code'      => 0 ,
                    'mesg'      => 'Player logged in',
                    'result'    => [
                        'playerName'    => $username ,
                        'playerId'      => $playerId,
                        'token'         => $player_token
                    ]
                ];
				redirect($this->input->post('goto_url')."?token=".$player_token.'&username='.$username);
                // header('Content-Type: application/json');
				// echo json_encode($ret);
			}
            redirect($this->input->post('goto_url'));
        }

		if ($email_sent_token) {
			// $this->utils->debug_log('OGP-19808.1963.email_sent_token',$email_sent_token);
			$this->goActiveAccount($playerId);
		} else {
			// $this->utils->debug_log('OGP-19808.1967.here!!!');
			if ($this->operatorglobalsettings->getSettingJson('redirect_after_registration') == 2) {

				$this->goWebsiteHome();
			} else {

				$this->goPlayerHome();
			}
		}
	}

	public function saveDuplicateContactNumberHistory($playerId, $contactNumber){
		$this->load->model(['duplicate_contactnumber_model', 'player_model']);
		$offset = 1;
		$isDuplicate = $this->player_model->verifyContactAvailable($contactNumber, $offset);
		if ($isDuplicate) {
			$duplicateContactNumberUser = $this->player_model->getDuplicateContactNumberUser($contactNumber, $playerId);
			$data = array(
				'player_id'	=> $playerId,
				'contact_number' => $contactNumber,
				'duplicate_user' => implode(",",$duplicateContactNumberUser),
				'created_at' => $this->utils->getNowForMysql(),
			);

			$this->duplicate_contactnumber_model->insertDuplicateContactNumberHistory($data);
		}
	}

	/**
	 * overview : activate email
	 *
	 * @return  page
	 */
	public function iframe_activate() {
		$this->load->model(['wallet_model']);
		$player_id = $this->authentication->getPlayerId();
		$player = $this->player_functions->getPlayerById($player_id, null);
		$data['player'] = $player;
		$origin = '*';
		$result = array('act' => 'iframe_login');
		$result['success'] = true;
		$result['keepOpen'] = true;
		$result['playerName'] = $player['username'];
		$result['playerId'] = $player_id;
		//add token
        $result['token'] = $this->authentication->getPlayerToken();
		$data['result'] = str_replace("'", "\\'", json_encode($result));
		$data['origin'] = $origin;
		$data['currentLang'] = $this->language_function->getCurrentLanguage();
		$data['playerStatus']=null;
		$this->loadTemplate(lang('title.activate_account'), '', '', 'player');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/auth/activate_account', $data);
		$this->template->render();
	}

	/**
	 * Change status on active field player table
	 *
	 * @return  page
	 */
	public function resendEmail($from_security_setting = false) {
		$this->load->model(['email_template_model', 'email_verification']);

		$playerId = $this->getLoggedPlayerId();

		// OGP-23086: check if still in cool down period of last verify email
		$time_last_vemail_redis_key = "time_last_verification_email_{$playerId}";
		$time_last_vemail = intval($this->utils->readRedis($time_last_vemail_redis_key));
		$verify_email_cd_interval = intval($this->utils->getConfig('verification_email_cooldown_time_sec'));
		$this->utils->debug_log(__METHOD__, [ 'time_last_vemail' => $time_last_vemail, 'verify_email_cd_interval' => $verify_email_cd_interval, 'time_passed' => (time() - $time_last_vemail) ]);
		if ($time_last_vemail > 0 && (time() - $time_last_vemail) < $verify_email_cd_interval) {
			$message = lang('Still in cool down period of last verification email, please try later');
			$this->utils->debug_log(__METHOD__, 'still in cd period', $message);
			if($from_security_setting){
				$result = ['success' => FALSE, 'message' => $message];
				return $this->returnJsonResult($result);
	        }
	        else{
				$this->alertMessage(self::MESSAGE_TYPE_WARNING, $message);
				return;
	        }
		}

		$this->utils->writeRedis($time_last_vemail_redis_key, time());

		#sending email
		$this->load->library(['email_manager']);
		$template_params = array('player_id' => $playerId);
		$template_name = 'player_verify_email';
        $email = $this->player->getPlayerById($playerId)['email'];
		$resetCode = null;
		if ($this->utils->getConfig('enable_verify_mail_via_otp')) {
			# Obtain the reset code
			$resetCode = $this->generateResetCode($playerId, true);
			$this->utils->debug_log("Reset code: ", $resetCode);
			$template_params['verify_code'] = $resetCode;
		}
		$template = $this->email_manager->template('player', $template_name, $template_params);
        $template_enabled = $template->getIsEnableByTemplateName(true);
        if ($template_enabled['enable']) {
			// $email = $this->player->getPlayerById($playerId)['email'];
        	$job_token = $template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $playerId);
        	$message = lang('notify.96');
			$record_id = $this->email_verification->recordReport($playerId, $email, $template_name, $resetCode, $job_token);

	        if($from_security_setting){
				$result = ['success' => TRUE, 'message' => $message];
				$this->returnJsonResult($result);
				return;
	        }
	        else{
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
	        }
        } else {
			$record_id = $this->email_verification->recordReport($playerId, $email, $template_name, $resetCode, null, email_verification::SENDING_STATUS_FAILED);

        	$message = lang('Security') . ' - ' . lang('Email Verification') .'<br>'. $template_enabled['message'];
        	$this->alertMessage(self::MESSAGE_TYPE_WARNING, $message);

	        if($from_security_setting){
				$result = ['success' => FALSE, 'message' => $message];
				$this->returnJsonResult($result);
				return;
	        }
        }

		$this->goPlayerHome();
	}

	/**
	 * overview : verify Email for registration or promo application
	 *
	 * @param 	int  $id  player id
	 * @param 	string  $promoCode  player promo id
	 * @return  rendered Template
	 */
	public function verify($id, $promoCode = '') {
		$this->load->model(array('player_model','email_template_model'));
		$this->load->library(array('session'));
		$this->utils->debug_log("player verify email processing id [$id]");

		# logout current player first
		if($this->authentication->isLoggedIn()) {
			$this->authentication->logout();
			$this->session->sess_create();
		}

		if(empty($id)){
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.113'));
			$this->goPlayerHome();
		}

		# Refer to: Email_template_player_verify_email::getVerifyLink
		# first 32 digits are md5 encoded playerId
		# last 32 digits are md5 encoded expired_time
		# in the middle is playerId
		$encoded_playerId     = substr($id, 0, 32);
		$encoded_expired_time = substr($id, -32);
		$playerId             = substr($id, 32, -32);

		if(empty($playerId)) {
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.113'));
			$this->goPlayerHome();
		}

		$this->utils->debug_log("verifying playerId [$playerId]");

		if(md5($playerId) != $encoded_playerId){
			$this->utils->debug_log("verifying playerId [$id], encoded_playerId is wrong");
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.113'));
			$this->goPlayerHome();
		}

		$player = $this->player_model->getPlayerById($playerId);
		if(md5($player->email_verify_exptime) != $encoded_expired_time){
			$this->utils->debug_log("verifying playerId [$id], encoded_expired_time is wrong");
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.113'));
			$this->goPlayerHome();
		}

		$now = new DateTime();
		$expired_time = new DateTime($player->email_verify_exptime);
		if($now > $expired_time){
			$this->utils->debug_log("verifying playerId [$id], link expired");
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.112'));
			$this->goPlayerHome();
		}

		$verified = $this->player_model->isVerifiedEmail($playerId);
		if($verified){
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.12'));
		} else {
			# set to verified
			$success = $this->player_model->verifyEmail($playerId);
			if ($success) {
				// -- add player update history
				$username = !$this->authentication->getUsername() ? $this->player_model->getUsernameById($playerId) : $this->authentication->getUsername();
				$this->savePlayerUpdateLog($playerId, lang('Email verified by player: ') . ' ' . $username, $username);
				$this->syncPlayerCurrentToMDBWithLock($playerId, $username, false);

				# sending email
				$this->load->library(['email_manager']);
		        $template = $this->email_manager->template('player', 'player_verify_email_success', array('player_id' => $playerId));
		        $template_enabled = $template->getIsEnableByTemplateName(true);
		        if ($template_enabled['enable']) {
		        	$email = $this->player->getPlayerById($playerId)['email'];
		        	$template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $playerId);
		        }

				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('notify.11'));
			} else {
				$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('notify.113'));
			}
		}

		$this->goPlayerHome();
	}

	/**
     * overview : verify Email by OTP code
     *
     * @param 	int  $id  player id
     * @param 	string  $promoCode  player promo id
     * @return  rendered Template
     */

	// public function verifyMailByOTPCode($player_id = null) {
	public function verifyMailByOTPCode($player_id = null, $otp_code = null, $from_comapi = false) {
		$player_id = $player_id?: $this->getLoggedPlayerId();
		// $otp_code = $this->input->post('otp_code');
		$otp_code = $otp_code ?: $this->input->post('otp_code');
		$this->utils->debug_log(__METHOD__, [ 'player_id' => $player_id, 'otp_code' => $otp_code ]);
		$this->load->model(['player_model']);
		$result = $this->player_model->getPlayer(array(
			'playerId' => $player_id,
			'resetCode' => $otp_code,
			'resetExpire >' => $this->utils->getNowForMysql(),
		));

		if ($result) {
			$verified = $this->player_model->isVerifiedEmail($player_id);
			if(!$verified){
				// -- add player update history
				$success = $this->player_model->verifyEmail($player_id);
				if ($success) {
					$username = !$this->authentication->getUsername() ? $this->player_model->getUsernameById($player_id) : $this->authentication->getUsername();
					if (!$from_comapi) {
						$this->savePlayerUpdateLog($player_id, lang('Email verified by player: ') . ' ' . $username, $username);
						$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);
					}

					# sending email
					$this->load->library(['email_manager']);
					$template = $this->email_manager->template('player', 'player_verify_email_success', array('player_id' => $player_id));
					$template_enabled = $template->getIsEnableByTemplateName(true);
					if ($template_enabled['enable']) {
						$email = $this->player->getPlayerById($player_id)['email'];
						$template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $player_id);
					}
				} else {
					// if (!$from_comapi) {
					// 	$this->returnJsonResult(['success'=> false]);
					// 	return;
					// }
					// else {
					// 	return false;
					// }
					return $from_comapi ? false : $this->returnJsonResult(['success'=> false]);
				}
			}
			// if (!$from_comapi) {
			// 	$this->returnJsonResult(['success'=> true]);
			// }
			// else {
			// 	return true;
			// }
			return $from_comapi ? true : $this->returnJsonResult(['success'=> true]);
		} else {
			// if (!$from_comapi) {
			// 	$this->returnJsonResult(['success'=> false]);
			// }
			// else {
			// 	return false;
			// }
			return $from_comapi ? false : $this->returnJsonResult(['success'=> false]);
		}
	}

	/**
	 * overview : logout
	 */
	public function iframe_logout() {
        $logout_result = $this->authentication->logout();

        $origin = '*';

        $result = [
            'act' => 'iframe_logout',
            'success' => true,
            'message' => lang('logout_successfully'),
            'redirect_url' => $logout_result['redirect_url']
        ];

        $data = array('result' => str_replace("'", "\\'", json_encode($result)), 'origin' => $origin);
		$this->load->view('player/iframe_callback', $data);
	}

	/**
	 * Compare sms verification
	 *
	 * @param integer $contact_number
	 * @param integer $sms_verification_code
	 * @return void
	 */
	public function compare_sms_verification($contact_number = null, $sms_verification_code = null){
		$this->load->model(['sms_verification','player_model']);

		if($this->utils->getConfig('disable_player_register_and_redirect_to_login')){
            return redirect('/iframe/auth/login');
        }

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
		}
		if($this->authentication->isLoggedIn()){
            $this->goPlayerHome();
		}

		$success = false;
		$message=lang('Verify SMS Code Failed');

		$sessionId = $this->session->userdata('session_id');


		$_request = $this->getInputGetAndPost();
		if( empty($contact_number) ){
			$contact_number = $_request['contact_number'];
		}
		if( empty($sms_verification_code) ){
			$sms_verification_code = $_request['sms_verification_code'];
		}

		if( ! empty($contact_number)
			&& ! empty($sms_verification_code)
		){
			$mobileNumber = $contact_number;
			$code = $sms_verification_code;
			$playerId = null; // not yet be a player.
			$result = $this->sms_verification->compareSmsVerificationCode(null, $sessionId, $mobileNumber, $code);
			if($result['boolean'] === true){
				$success = true;
				$message = '';
			}else{
				$success = false;
			}
		}

		$result = ['success'=>$success, 'message'=>$message];

		$this->returnJsonResult($result);
	} // EOF compare_sms_verification

	/**
	 * update phone verification flag
	 *
	 * @param  string $contact_number
	 * @param  string $sms_verification_code
	 * @return json
	 */
	public function update_sms_verification($contact_number, $sms_verification_code, $restrict_area = null, &$result = []) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }
		$playerId = $this->authentication->getPlayerId();
		if (static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'update_sms_verification')) {
			return $this->_show_last_check_acl_response('json');
		}
		$this->load->library('session');
		$this->load->model(['sms_verification','player_model']);

		$session_id = $this->session->userdata('session_id');

		$success = false;
		$message=lang('Verify SMS Code Failed');

		$player = $this->player_functions->getPlayerById($playerId);

		if ($contact_number == 'verified') {
			# Try to get mobile number from player profile if not supplied
			$this->load->library(array('authentication', 'player_functions'));
			$contact_number = $player['contactNumber'];
			if (!$contact_number) {
				$message=lang('Verify SMS Code Failed');
			}
		}
		if(!empty($playerId) && !empty($session_id) && !empty($contact_number) && !empty($sms_verification_code)){

			$success = !isset($sms_verification_code) || $this->sms_verification->validateVerificationCode($playerId, $session_id, $contact_number, $sms_verification_code, $restrict_area);

			if(!$success) {
				$this->utils->debug_log('========== validate sms_verification_code from back office =====', $success);
				// validte verification code from back office
				$success = $this->sms_verification->validateVerificationCode($playerId, null, $contact_number, $sms_verification_code);
			}
			$this->utils->debug_log('========== sms_verification_code result =====', $success);
			if($success){
				$success=$this->player_model->updateAndVerifyContactNumber($playerId, $contact_number);
				if(!$success){
					$message=lang('Verify SMS Code Failed');
				}
			}else{
				$message=lang('Verify SMS Code Failed');
			}

			if($success){
				$username= $this->authentication->getUsername();
				$this->savePlayerUpdateLog($playerId, lang('Phone verified by player: ') . ' ' . $username, $username);
				$message=lang('Verify SMS Code Successfully');

				$this->syncPlayerCurrentToMDBWithLock($playerId, $username, false);
			}
		}

		$result = ['success'=>$success, 'message'=>$message];

		$this->returnJsonResult($result);
	}

	/**
     * @deprecated should be replaced by player_center/profile
	 * view playere settings or details
	 *
	 *
	 * @return rendered template
	 */
	public function iframe_playerSettings() {
		$player_id = $this->authentication->getPlayerId();
		$data['current_promo'] = "";
		$data['player'] = $this->player_functions->getPlayerById($player_id);
		$data['bank_details'] = $this->player_functions->getBankDetails($player_id);
		$data['currentLang'] = $this->language_function->getCurrentLanguage();
		$data['default_prefix_for_username'] = $this->config->item('default_prefix_for_username');
		$data['fields'] = $this->getVisibleFielsForPlayer();
		if (!empty($this->session->userdata('promoCode'))) {
			$promo = $this->player_functions->checkPromoCodeExist($this->session->userdata('promoCode'));
			$data['current_promo'] = $this->player_functions->checkIfAlreadyGetPromo($player_id, $promo['promoId']);
		}

		$this->loadTemplate(lang('pi.16'), '', '', 'settings');
		$this->template->write_view('main_content',  $this->utils->getPlayerCenterTemplate() . '/player/view_player_settings', $data);
		$this->template->render();
	}

	/**
	 * Detect inputs has XSS ?
	 * @param array $inputs $_GET or $_POST, usually apply into the return of $this->getInputGetAndPost().
	 * @return array $return The formats,
	 * - $return['result'] boolean Return true while XSS detected else false.
	 * - $return['inupts'] array  depend by param, $inputs, ex:
	 * $return['inupts'][foo]['orig'] = 'boo'
	 * $return['inupts'][foo]['clean'] = 'boo'
	 * $return['inupts'][foo]['hasXSS'] = false
	 * $return['inupts'][foo1]['orig'] = '<script> alert(document.domain) </script>'
	 * $return['inupts'][foo1]['clean'] = '[removed] alert&#40;document.domain&#41; [removed]'
	 * $return['inupts'][foo1]['hasXSS'] = true
	 * ...
	 *
	 */
	public function detectXSSWithParams($inputs){
		$return = array();
		$returnBool = false;
		$returnInupts = array();
		reset ($inputs);

		foreach ($inputs as $key => $value) {
			$xssClean = $this->security->xss_clean($value);
			$returnInupts[$key]['orig'] = $inputs[$key];
			$returnInupts[$key]['clean'] = $xssClean;

			if( $xssClean == $value){ // without xss
				$returnInupts[$key]['hasXSS'] = false;
				$returnBool = $returnBool || false;
			}else{ // has xss
				$returnInupts[$key]['hasXSS'] = true;
				$returnBool = $returnBool || true;
				break;
			}
		}
		$return['result'] = $returnBool;
		$return['inupts'] = $returnInupts;
		return $return;
	}

	public function postEditPlayer() {

		$this->utils->debug_log('enter postEditPlayer');

		if (empty($_POST) && empty($_FILES)) {
			return $this->_saveProfileSuccess();
		}

        $this->formRulesEditPlayer();
		$inputs = $_REQUEST; // orig inputs.
		$hasXSS = $this->detectXSSWithParams($inputs);
		/// Ref. to
		// https://3v4l.org/SWdT0#v500
		// https://blag.kazeno.net/development/access-private-protected-properties
		//
		// suppert for PHP 5.0.0 - 5.6.40, hhvm-3.10.1 - 3.22.0, 7.0.0 - 7.4.0alpha1
		$fv = (array)$this->form_validation;
		$fieldData = $fv["\0*\0" .'_field_data']; /// get protected attr.

		if( $hasXSS['result'] ) {

			// Get Field Name has XSS.
			$hasXSSInputKey = '';
			reset ($hasXSS['inupts']);
			while ( list($key, $value) = each ($hasXSS['inupts']) ) {
				if( $value['hasXSS'] ){
					$hasXSSInputKey = $key;
					break;
				}
			}
			$label = 'Inputs';
			// Get label of the field.
			if( ! empty($fieldData[$hasXSSInputKey]) ){
				$label = $fieldData[$hasXSSInputKey]['label'];
			}

			$this->utils->debug_log('profile form validation has XSS: ', $hasXSS); // 可以提示輸入的狀況，跟有問題的狀況。
			$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
			$message = '<ul class="validation_errors"><li class="error_entry">'.$label.' is unacceptable.</li></ul>'; //@todo 提示什麼欄位，系統無法接受（私下是 XSS 的疑慮不用告訴玩家）
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

			$this->profile();
		}else if ($this->form_validation->run() == false) {
			$this->utils->debug_log('profile form validation', $this->form_validation->error_string());

            $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
            $message = '<ul class="validation_errors">' . validation_errors('<li class="error_entry">', '</li>') . '</ul>';

			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}

			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

			$this->profile();
		} else {
			$player_id = $this->authentication->getPlayerId();

			$region 			= $this->input->post('region');
            $address 			= $this->input->post('address');
			$address2 			= $this->input->post('address2');
			$address3 			= $this->input->post('address3');
			$zipcode 			= $this->input->post('zipcode');
			$city 				= $this->input->post('city');
			$language 			= $this->input->post('language');
			$citizenship 		= $this->input->post('citizenship');
            $dialing_code    	= $this->input->post('dialing_code');
			$contact_number 	= $this->input->post('contact_number');
			$sms_verification_code = $this->input->post('sms_verification_code');
			$im_account 		= $this->input->post('im_account');
			$im_account2 		= $this->input->post('im_account2');
			$im_account3 		= $this->input->post('im_account3');
			$im_account4 		= $this->input->post('im_account4');
			$im_account5 		= $this->input->post('im_account5');
			$birthplace 		= $this->input->post('birthplace');
			$today 				= date("Y-m-d H:i:s");
			# Special fields, only allow edit when original value is empty
			$firstName 			= $this->input->post('name');
			$lastName 			= $this->input->post('lastname');
			$gender 			= $this->input->post('gender');
			$birthdate 			= $this->input->post('birthdate');
			$email 				= $this->input->post('email');
			$id_card_number 	= $this->input->post('id_card_number');
            $id_card_type       = $this->input->post('id_card_type');
            $residentCountry    = $this->input->post('residentCountry');
            $pix_number 	    = $this->input->post('pix_number');

			$data['currentLang'] = $this->language_function->getCurrentLanguage();
			$player = $this->player_functions->getPlayerById($this->authentication->getPlayerId());
			$data = ['updatedOn' => $today];

			$player_email = array();
			$this->player_functions->editPlayer($data, $this->authentication->getPlayerId());
			if (empty($language)) {
				if(!empty($player['language'])){
					$language = $player['language'];

				}else{
					$language = $this->utils->getConfig('default_player_language');
				}

			}

			$data = array();

            $this->load->model(array('registration_setting'));

			# Editing of special fields

			if (!empty($firstName) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'firstName')) {
				$data['firstName'] = $firstName;
			}


			if (!empty($lastName) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'lastName')) {
				$data['lastName'] = $lastName;
			}


			if (!empty($birthdate) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'birthdate')) {
				$data['birthdate'] = $birthdate;
			}


			if (!empty($gender) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'gender')) {
				$data['gender'] = $gender;
			}

            if (!empty($language) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'language')) {
                $data['language'] = $language;
            } else if (!empty($player['language'])) {
                $data['language'] = $language;
            }

            $disable_email_edit = $player['verified_email'];
            if (!empty($email) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'email', $disable_email_edit)) {
				$player_email['email'] = strtolower($email);
			}

            $disable_contact_number_edit = ($this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone') || $player['verified_phone']);
            if (!empty($contact_number) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'contactNumber', $disable_contact_number_edit)) {
                $data['contactNumber'] = $contact_number;
                if($this->config->item('allow_first_number_zero') && substr($contact_number,0,1) == '0'){
		        	$contact_number = substr($contact_number,1);
		        }
            }

			if (!empty($sms_verification_code)) {

				$enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
				if( ! empty($enable_OGP19808) ){ // enable the default
					if ( empty($contact_number) ){
						$contact_number = $player['contactNumber'];
					}
				}

				$verify_result = [];
				$usage = null;
				if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
					$usage = 'sms_api_accountinfo_setting';
				}

				$this->update_sms_verification($contact_number,$sms_verification_code, $usage, $verify_result);
// $this->utils->debug_log('OGP-19808.2430.$verify_result', $verify_result);

			}

            if (!empty($dialing_code) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'dialing_code')) {
                $data['dialing_code'] = $dialing_code;
            }

            $disable_im_edit = ($this->utils->isEnabledFeature('enabled_show_player_obfuscated_im'));
			if (!empty($im_account) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'imAccount', $disable_im_edit)) {
			    $data['imAccount'] = $im_account;
			}

			if (!empty($im_account2) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'imAccount2', $disable_im_edit)) {
			    $data['imAccount2'] = $im_account2;
			}

			if (!empty($im_account3) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'imAccount3', $disable_im_edit)) {
			    $data['imAccount3'] = $im_account3;
			}

			if (!empty($im_account4) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'imAccount4', $disable_im_edit)) {
			    $data['imAccount4'] = $im_account4;
			}

			if (!empty($im_account5) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'imAccount5', $disable_im_edit)) {
			    $data['imAccount5'] = $im_account5;
			}

			if (!empty($region) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player, 'region')) {
                $data['region'] = $region;
            }

			if (!empty($address) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'address')) {
				$data['address'] = $address;
			}
			if (!empty($address2) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'address2')) {
				$data['address2'] = $address2;
			}

			if (!empty($address3) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'address3')) {
				$data['address3'] = $address3;
			}

			if (!empty($zipcode) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'zipcode')) {
				$data['zipcode'] = $zipcode;
			}

			if (!empty($city) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'city')) {
				$data['city'] = $city;
			}

			if (!empty($citizenship) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'citizenship')) {
				$data['citizenship'] = $citizenship;
			}

            if (!empty($birthplace) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'birthplace')) {
                $data['birthplace'] = $birthplace;
            }

            if (!empty($residentCountry) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'residentCountry')) {
                $data['residentCountry'] = $residentCountry;
            }

			if (!empty($id_card_number) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'id_card_number')) {
				$data['id_card_number'] = $id_card_number;
			}

            if (!empty($id_card_number) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'id_card_type')) {
                $data['id_card_type'] = $id_card_type;
            }

            if (!empty($pix_number) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'pix_number')) {
				$data['pix_number'] = $pix_number;
			}
			//set language
			$this->language_function->setCurrentLanguage($this->language_function->langStrToInt($language));

			//save changes to playerupdatehistory
			$modifiedFields = $this->checkModifiedFields($player_id, array_merge($data,$player_email));
			$this->savePlayerUpdateLog($player_id, lang('lang.edit') . ' ' . lang('lang.playerinfo') . ' (' . $modifiedFields . ')', $this->authentication->getUsername());

			// Update player preferences (OGP-2700 and on)
			$enable_auto_transfer_postval = $this->input->post('enable_auto_transfer');
			if (!empty($enable_auto_transfer_postval)) {
				$this->load->model('player_preference');
				$enable_auto_transfer_prefval = ($enable_auto_transfer_postval == 'yes');
				$this->player_preference->storePrefItem($player_id, 'auto_transfer', $enable_auto_transfer_prefval);
			}

			#OGP-13922 if xinyan status = 1 and edit player name or contactNumber, update status to enable the xinyan validation button
			// if($this->utils->isEnabledFeature('enable_show_trigger_XinyanApi_validation_btn')) {
			// 	$this->load->model(array('player_model','player_api_verify_status'));

			// 	$status           = $this->player_api_verify_status->getApiStatusByPlayerId($player_id);
			// 	$getcontactNumber = $this->player_model->getPlayerContactNumber($player_id);
			// 	$playerDetails 	  = $this->CI->player_model->getPlayerDetails($player_id);
			// 	$getPlayerName    = $playerDetails[0]['firstName'];

			// 	if($status == player_api_verify_status::API_RESPOSE_SUCCESS){
			// 		if($contact_number && $contact_number != $getcontactNumber){
			// 			$this->player_api_verify_status->updateApiStatusByPlayerId($player_id, player_api_verify_status::API_UNKNOWN);
			// 			$this->CI->utils->debug_log('===========================contact_number update xinyan status success by player ');
			// 		}
			// 		if($firstName && $firstName != $getPlayerName){
			// 			$this->player_api_verify_status->updateApiStatusByPlayerId($player_id, player_api_verify_status::API_UNKNOWN);
			// 			$this->CI->utils->debug_log('===========================firstName update xinyan status success by player');
			// 		}
			// 	}
			// }


			if (!empty($data)) {
				$this->player_functions->editPlayerDetails($data, $this->authentication->getPlayerId());
			}

			if (!empty($player_email)) {
				$this->player_functions->editPlayerEmail($player_email, $this->authentication->getPlayerId());
			}else if(!$this->registration_setting->isRegistrationFieldVisible('Email') && empty($email) && !$this->player_functions->checkAccountFieldsIfRequired('Email')){
				#OGP-17687
				$origin = $this->player_model->getPlayerInfoById($this->authentication->getPlayerId());
				if ($origin['verified_email']) {
				}elseif (!$this->registration_setting->checkAccountInfoFieldAllowEdit($origin,'email', $origin['verified_email'])) {
				}
				else {
					$player_email['email'] = '';
					$this->player_functions->editPlayerEmail($player_email, $this->authentication->getPlayerId());
				}
			}

            $settings = $this->utils->getConfig('limit_update_player_times');
            if(!empty($settings)){         
                $_fields = array_merge($data, $player_email);
                foreach($_fields as $fieldName => $fieldValue){       

                    $isDiff = (isset($player[$fieldName]) && $player[$fieldName] != $fieldValue);
                    
                    if(isset($settings[$fieldName]) && $isDiff){
                        $this->player_functions->countUpdatedFieldTimes($player_id, $fieldName);
                    }
                }
            }

			//sync
			$this->load->model(['player_model']);
			$username=$this->player_model->getUsernameById($player_id);
			$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

			$uploadProfile = $this->uploadProfilePicture();
			if ($uploadProfile['status'] == 'error') {
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $uploadProfile['msg']);
				$this->goPlayerSettings($this->authentication->getPlayerId());
				return;
			}

            if($this->utils->getConfig('enable_fast_track_integration')) {
                $this->load->library('fast_track');
                $this->fast_track->updateUser($player_id);
            }

			return $this->_saveProfileSuccess();
		}
	}

	public function postEditPlayerContactNumber() {

		$this->utils->debug_log('enter postEditPlayerContactNumber');

		if (empty($_POST) && empty($_FILES)) {
			return $this->_saveProfileSuccess();
		}

        if (isset($_POST["contact_number"]) && $this->player_functions->checkAccountFieldsIfRequired('Contact Number')) {
			if ($this->utils->isEnabledFeature('allow_player_same_number')) {
				$this->form_validation->set_rules('contact_number', lang('Contact Number'), 'trim|required|xss_clean');
			} else {
				$this->form_validation->set_rules('contact_number', lang('Contact Number'), 'trim|required|xss_clean|callback_check_contact');
			}
		} elseif ($this->utils->isEnabledFeature('allow_player_same_number')) {
			$this->form_validation->set_rules('contact_number', lang('Contact Number'), 'trim|xss_clean');
		} else {
			$this->form_validation->set_rules('contact_number', lang('Contact Number'), 'trim|xss_clean|callback_check_contact');
		}

		$inputs = $_REQUEST; // orig inputs.
		$hasXSS = $this->detectXSSWithParams($inputs);
		/// Ref. to
		// https://3v4l.org/SWdT0#v500
		// https://blag.kazeno.net/development/access-private-protected-properties
		//
		// suppert for PHP 5.0.0 - 5.6.40, hhvm-3.10.1 - 3.22.0, 7.0.0 - 7.4.0alpha1
		$fv = (array)$this->form_validation;
		$fieldData = $fv["\0*\0" .'_field_data']; /// get protected attr.

		if( $hasXSS['result'] ) {

			// Get Field Name has XSS.
			$hasXSSInputKey = '';
			reset ($hasXSS['inupts']);
			while ( list($key, $value) = each ($hasXSS['inupts']) ) {
				if( $value['hasXSS'] ){
					$hasXSSInputKey = $key;
					break;
				}
			}
			$label = 'Inputs';
			// Get label of the field.
			if( ! empty($fieldData[$hasXSSInputKey]) ){
				$label = $fieldData[$hasXSSInputKey]['label'];
			}

			$this->utils->debug_log('profile form validation has XSS: ', $hasXSS); // 可以提示輸入的狀況，跟有問題的狀況。
			$this->form_validation->set_error_delimiters('<div class="error">', '</div>');
			$message = '<ul class="validation_errors"><li class="error_entry">'.$label.' is unacceptable.</li></ul>'; //@todo 提示什麼欄位，系統無法接受（私下是 XSS 的疑慮不用告訴玩家）
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

			$this->profile();
		}else if ($this->form_validation->run() == false) {
			$this->utils->debug_log('profile form validation', $this->form_validation->error_string());

            $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
            $message = '<ul class="validation_errors">' . validation_errors('<li class="error_entry">', '</li>') . '</ul>';

			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}

			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

			$this->profile();
		} else {
			$player_id = $this->authentication->getPlayerId();
			$contact_number 	= $this->input->post('contact_number');
			$today 				= date("Y-m-d H:i:s");
			$player = $this->player_functions->getPlayerById($this->authentication->getPlayerId());
			$data = ['updatedOn' => $today];

			$this->player_functions->editPlayer($data, $this->authentication->getPlayerId());

			$data = array();
            $this->load->model(array('registration_setting'));
            $disable_contact_number_edit = ($this->utils->isEnabledFeature('enabled_show_player_obfuscated_phone') || $player['verified_phone']);
            if (!empty($contact_number) && $this->registration_setting->checkAccountInfoFieldAllowEdit($player,'contactNumber', $disable_contact_number_edit)) {
                $data['contactNumber'] = $contact_number;
            }

			//save changes to playerupdatehistory
			$modifiedFields = $this->checkModifiedFields($player_id, $data);
			$this->savePlayerUpdateLog($player_id, lang('lang.edit') . ' ' . lang('lang.playerinfo') . ' (' . $modifiedFields . ')', $this->authentication->getUsername());

			if (!empty($data)) {
				$this->player_functions->editPlayerDetails($data, $this->authentication->getPlayerId());
			}

			//sync
			$this->load->model(['player_model']);
			$username=$this->player_model->getUsernameById($player_id);
			$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

			return $this->_saveProfileSuccess();
		}
	}

	protected function _saveProfileSuccess(){
		$message = lang('notify.24');

		if ($this->input->is_ajax_request()) {
			$this->returnJsonResult(array('status' => 'success', 'msg' => $message));
			return;
		}

		$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);

		$this->goPlayerSettings($this->authentication->getPlayerId());
	}

	public function registration_successful(){
		$player_id = $this->authentication->getPlayerId();
		$data['player'] = $this->player_model->getPlayerInfoDetailById($player_id);

		$this->loadTemplate(lang('Register'), '', '', '');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/player/register_success', $data);
		$this->template->render();
	}

	public function check_login_contact_number($contact_number){
        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }
		if (!empty($contact_number)) {
			//exist
			$this->load->model(['player_model']);
			$result = $this->player_model->checkContactExist($contact_number);
			if (!$result) {
				$this->form_validation->set_message('check_login_contact_number', $contact_number . " " . lang('gen.error.not_exist'));
			}
			return $result;
		} else {
			return true;
		}
	}

    public function view_mobile_login(){
        $this->output->set_header('Access-Control-Allow-Origin:*');
        $this->load->model(array('operatorglobalsettings', 'country_rules'));
        $this->load->library('user_agent');

        $data['playercenter_logo'] = $this->utils->getPlayerCenterLogoURL();

        $data['snackbar'] = isset($snackbar) ? $snackbar : [];
        $data['currentLang'] = $this->language_function->getCurrentLanguage();
        $data['forget_password_enabled'] = $this->operatorglobalsettings->getSettingJson('forget_password_enabled');
        $data['login_captcha_enabled'] = $this->operatorglobalsettings->getSettingJson('login_captcha_enabled');
        $data['remember_password_enabled'] = $this->operatorglobalsettings->getSettingJson('remember_password_enabled');
        $this->session->set_userdata('currentLanguage', $this->language_function->getCurrentLanguage());

        $template = $this->utils->getPlayerCenterTemplate();

        $title=lang('lang.login_title');
        $view=$template . '/auth/login_mobile_account';
        $activenav = '';
        $description = '';
        $keywords = '';

        $this->template->set_template($this->utils->getPlayerCenterTemplate(false));

        # scripts that was originally written in template
        $base_path = base_url() . $this->utils->getPlayerCenterTemplate();
        $this->template->add_js('/resources/third_party/jquery-wizard/0.0.7/jquery.wizard.min.js');
        $this->template->write('skin', 'template1.css');
        $this->template->write('title', $title);
        $this->template->write('description', $description);
        $this->template->write('keywords', $keywords);
        $this->template->write('activenav', $activenav);
        $this->template->write_view('main_content', $view, $data);
        $this->template->render();
    }

	public function mobile_login() {
        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        $this->load->model('operatorglobalsettings');
        $this->form_validation->set_rules('contact_number', lang('Mobile Number'), 'trim|required|xss_clean|callback_check_login_contact_number');

        if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
			$params = array('contact_number','sms_api_login_setting');
			$rulesStr = 'trim|required|xss_clean|callback_check_sms_verification['. json_encode($params) .']';
		}else{
			$rulesStr = 'trim|required|xss_clean|callback_check_sms_verification[contact_number]';
		}
        $this->form_validation->set_rules('sms_verification_code', lang('SMS Code'), $rulesStr);

        $result=[];

        if ($this->form_validation->run()) {
            $contact_number = $this->input->post('contact_number');

            $this->load->library(array('player_library'));

            $login_result = $this->player_library->login_by_contact_number($contact_number);

            if(!$login_result['success']){
                $result['success'] = FALSE;
                $result['message'] = (is_array($login_result['errors']) && !empty($login_result['errors'])) ? end($login_result['errors']) : $login_result['errors'];

                return $result;
            }

            $next_url = $this->input->post('goto_url');
            if(empty($next_url)){
                $next_url = $this->utils->getPlayerHomeUrl();
            }

            $result['success'] = TRUE;
            $result['message'] = lang('Login successfully');
            $result['next_url'] = site_url($next_url);
        } else {
            $result['success'] = false;
            $result['message'] = lang('Login failed, wrong mobile number or SMS code');
        }

        $this->utils->debug_log($result);

        return $this->returnCommon(BaseController::MESSAGE_TYPE_SUCCESS, $result['message'], $result);
	}

	/**
	 * Sends an email with basic template
	 *
	 * @param  string $player_id Player's ID
	 * @param  string $email_template Chosen email template
	 * @param  string $subject Email subject
	 * @return boolean           Email sending status
	 * @author Cholo Miguel Antonio
	 */
	public function sendEmailWithBasicTemplate($player_id, $email_template, $subject) {
		$this->load->model(array('queue_result', 'player'));

		$player_info = $this->player->getPlayerById($player_id); // get player info

		// -- Email Template
		$emailTemplate = $this->email_setting->getEmailTemplatePromo($email_template);

		$replaced_variables = str_replace("[playername]", $player_info['lastName'] . ' ' . $player_info['firstName'], $emailTemplate);
		$replaced_variables = str_replace("[username]", $player_info['username'], $replaced_variables);

		$body = '<html><body>' . $replaced_variables['template'] . '</body></html>';

		$token = $this->utils->sendMail($player_info['email'], null, null, $subject, $body, Queue_result::CALLER_TYPE_PLAYER, $player_id);
		$this->utils->debug_log('email', $player_info['email'], 'query token', $token);

		return $token;
	}
}
////END OF FILE/////////
