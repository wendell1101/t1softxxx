<?php

require_once APPPATH . 'controllers/PlayerBaseController.php';
require_once dirname(__FILE__) . '/../modules/player_auth_module.php';
require_once dirname(__FILE__) . '/../modules/player_auth_facebook_module.php';
require_once dirname(__FILE__) . '/../modules/player_auth_google_module.php';

/**
 * Class Auth
 *
 * @property Player_library $player_library
 */
class Auth extends PlayerBaseController {

	use player_auth_module;
	use player_auth_facebook_module;
    use player_auth_google_module;

	################################################ CONSTANTS ################################################
	const FORBIDDEN_NAMES = ['admin', 'moderator', 'hoster', 'administrator', 'mod'];
	const MIN_USERNAME_LENGTH = 4;
	const MAX_USERNAME_LENGTH = 9;
	const MIN_PASSWORD_LENGTH = 4;
	const MAX_PASSWORD_LENGTH = 12;

	################################################ CONSTRUCTOR ################################################

	public function __construct() {
		parent::__construct();
		$this->load->helper(['url']);
		$this->load->library(['form_validation', 'authentication', 'player_functions', 'cms_function', 'template', 'pagination', 'api_functions', 'salt', 'cs_manager', 'email_setting', 'og_utility', 'game_platform/game_platform_manager', 'duplicate_account', 'user_agent', 'player_library']);
		$this->load->model(['player_model']);

		$this->setLanguageBySubDomain();
	}

	################################################ PAGES ################################################

	public function validate($name) {

		$value = $this->input->get($name, TRUE);
		$value = trim($value);

		if ($value) {

			switch ($name) {
			case 'username':
				$tracking_code=$this->getTrackingCode();
				$prefixOfPlayer=$this->getPrefixUsernameOfPlayer($tracking_code);

				$result = $this->player_functions->checkUsernameExist($prefixOfPlayer.$value);
				$this->output->set_content_type('application/json')->set_output(json_encode([
					'result' => !$result,
				]));
				break;

			case 'email':
				$result = $this->player_functions->checkEmailExist($value);
				$this->output->set_content_type('application/json')->set_output(json_encode([
					'result' => !$result,
				]));
				break;

			case 'referral_code':
				$result = $this->player_functions->checkIfReferralCodeExist($value);
				$this->output->set_content_type('application/json')->set_output(json_encode([
					'result' => !!$result,
				]));
				break;

			case 'affiliate_code':
				$result = $this->player_functions->checkIfAffiliateCodeExist($value);
				$this->output->set_content_type('application/json')->set_output(json_encode([
					'result' => !!$result,
				]));
				break;

			case 'contact_number':
				$result = $this->player_functions->checkContactExist($value);
				$this->output->set_content_type('application/json')->set_output(json_encode([
					'result' => !$result,
				]));
				break;

			default:
				# code...
				break;
			}

		}

	}


	################################################ PAGES ################################################
	public function check_captcha($val) {

		$this->utils->debug_log('check_captcha on auth', $val);

		$this->load->model('operatorglobalsettings');
		$rlt = true;
		if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled') || $this->operatorglobalsettings->getSettingJson('registration_captcha_enabled')) {
			$login_captcha = $this->input->post('login_captcha');
			if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')) && $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'] == 'hcaptcha'){
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
				//check admin secret key to pass captha
				$admin_captcha_secret_key = $this->utils->getConfig('admin_captcha_secret_key');
				$this->utils->debug_log('login_captcha', $login_captcha, 'admin_captcha_secret_key', $admin_captcha_secret_key);
				if (!empty($admin_captcha_secret_key) && $login_captcha == $admin_captcha_secret_key) {
					return true;
				}

				$active = $this->config->item('si_active');
				$current_host = $this->utils->getHttpHost();
				$active_domain_assignment = $this->config->item('si_active_domain_assignment');
				if( ! empty($active_domain_assignment[$current_host]) ){
					$active = $active_domain_assignment[$current_host];
				}
				$allsettings = array_merge( $this->config->item('si_general'), $this->config->item($active) );

				//check captcha first
				$this->load->library('captcha/securimage');
				$securimage = new Securimage($allsettings);

				$rlt = $securimage->check($login_captcha);

				$this->utils->debug_log('check_captcha result '.($rlt ? 'true' : 'false'), $rlt, $login_captcha, $this->session->all_userdata());
			}
			if(!$rlt){
				$this->form_validation->set_message('check_captcha', lang('error.captcha'));
			}
		}
		return $rlt;
	}

	const KEY_LOADED_LOGIN_PAGE='_loaded_login_page';

	# NOTE: Use redirect('iframe/auth/login','refresh'); to redirect back to PREVIOUS page after logging in.
	# NOTE: Use redirect('iframe/auth/login'); to redirect to DEFAULT page after log in (useful for methods like logout).
	public function login($shouldBeNothing=null) {

		$this->utils->debug_log('get ip from player', $this->input->ip_address(), isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : 'no x-real-ip');


		if($this->utils->getConfig('disable_player_register_and_login')){
			return show_error('No permission', 403);
		}

		if($this->_isBlockedPlayer()){
			return show_error('No permission', 403);
		}
		if(!empty($shouldBeNothing)){
			return show_error('No permission', 403);
		}

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

		#unset minicashier session
		$this->session->unset_userdata('view_template');
		$this->output->set_header('Access-Control-Allow-Origin:*');
		$this->load->model(array('operatorglobalsettings', 'country_rules', 'player'));
        $this->load->library(['user_agent', 'iovation_lib']);

		// $ip = $this->input->ip_address();

		// $isSiteBlock = $this->country_rules->getBlockedStatus($ip, 'blocked_www_m');

		// if($isSiteBlock) {
  //           list($city, $countryName) = $this->utils->getIpCityAndCountry($ip);
  //           $block_page_url = $this->country_rules->getBlockedPageUrl($countryName, $city);
		// 	if(empty($block_page_url)) {
		// 		return $this->returnErrorStatus(403, true);
		// 	} else {
		// 		redirect($block_page_url, 403);
		// 	}
		// }
		$gamePlatform = $this->input->post('gamePlatform');//check if using custom login
		$redirectUri = $this->input->post('redirectUri');//get redirect uri
		if(!$gamePlatform){
			if($this->authentication->isLoggedIn()){
	            if( $this->input->is_ajax_request() && $this->utils->is_mobile() ){
	                $this->returnJsonResult(array('status' => 'success', 'msg' => ''));
	                return;
	            }
				if (!empty($this->input->get('referrer'))) {
					$url = $this->input->get('referrer');
					$this->session->set_userdata('referrer', $url);
					return redirect($url);
				} else {
					return redirect('/');

				}

	        }
		}
		$this->utils->debug_log('is post', $this->isPostMethod());

        $data['is_enabled_iovation_in_player_login'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_player_login') && $this->CI->iovation_lib->isReady;

		if ($this->isPostMethod()) {

            $snackbar = [];
			$username = $this->input->post('username');
			$isAutoMachineUser = $this->player->isAutoMachineUser($username);

            //check white ip
            $isWhiteIP=$this->utils->getConfig('enabled_white_ip_on_login') && $this->ip->isWhiteIPForPlayer();
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
            if (!$ignoreACL && static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'iframe_auth_login')) {
                $this->utils->debug_log('block login on iframe_auth_login', $username, $this->utils->getIP());
	            $blockedIp=$this->utils->getIp();
	            $blockedRealIp=$this->utils->tryGetRealIPWithoutWhiteIP();
	            $succBlocked=$this->player_model->writeBlockedPlayerRecord($username, $blockedIp,
	                $blockedRealIp, Player_model::BLOCKED_SOURCE_PLAYER_CENTER_LOGIN);
	            if(!$succBlocked){
	                $this->utils->error_log('write blocked record failed', $username, $blockedIp, $blockedRealIp);
	            }
                return show_error('No permission', 403);
            }

            // Deprecated, if need to trigger login failed event, create a new event
			// if (!$ignoreACL && static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'login_notify')) {
			// 	$this->player_library->triggerPlayerLoginEvent('login using username:'. $username, $this->input->ip_address(), null);
			// }

			// if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled') && !$isAutoMachineUser && $this->input->post('login_captcha')) {
			if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled') && !$isAutoMachineUser) {
				$this->form_validation->set_rules('login_captcha', lang('label.captcha'), 'callback_check_captcha');
			}

			try {
		        if($this->_isBlockedByReferrerRule()){
		            return show_error('No permission', 403);
		        }
				if(!$this->_verifyAndResetCSRF()){
					$message = lang('Session timeout. Please refresh and try again.');
					throw new Exception(json_encode(['username'=>$message]));
				}

				$this->form_validation->set_rules('referer', 'lang:referer', 'trim|xss_clean');
				$this->form_validation->set_rules('username', 'lang:username', 'trim|required|xss_clean');
				$this->form_validation->set_rules('password', 'lang:password', 'trim|required|xss_clean');

				if (!$this->form_validation->run()) {
                    $captcha_err = form_error('login_captcha');
					$errors = validation_errors();
					if (!empty($captcha_err)) {
						throw new Exception(json_encode(array('captcha' => lang('error.captcha'))));
					} else {
						throw new Exception(json_encode($errors));
					}
				}

				$player = $this->CI->player_model->getPlayerByUsername($username);

				$password = $this->input->post('password');
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

                if ($data['is_enabled_iovation_in_player_login'] && !empty($player)) {
                    $ioBlackBox = $this->input->post('ioBlackBox');

                    if (!$this->utils->getConfig('allow_empty_blackbox') && empty($ioBlackBox)) {
                        $message = lang('notify.127');
                        $this->utils->error_log('Error player login missing ioBlackBox', $player);

                        if ($this->utils->is_mobile()) {
                            $message = json_encode($message);
                        } else {
                            $this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, $message);
                        }

                        if ($this->input->is_ajax_request()) {
                            $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
                            return;
                        }

                        redirect('/iframe/auth/login');
                    }

                    if (!empty($ioBlackBox)) {
                        $this->utils->debug_log('============================triggerRegisterToIovation');

                        $iovationparams = [
                            'player_id' => $player->playerId,
                            'ip'=> $this->utils->getIP(),
                            'blackbox' => $ioBlackBox,
                        ];

                        $iovationResponse = $this->CI->iovation_lib->registerToIovation($iovationparams, Iovation_lib::API_playerLogin);
                        $this->utils->debug_log('Post player login Iovation response', $iovationResponse);
                    }

                    //check if auto block and to block based on the result
                    $data['is_enabled_auto_block_player_if_denied_login'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_auto_block_player_if_denied_login');

                    if ($data['is_enabled_auto_block_player_if_denied_login'] && isset($iovationResponse['iovation_result']) && $iovationResponse['iovation_result'] == 'D') {
                        $this->player_model->blockPlayerWithoutGame($player->playerId);
                        $this->authentication->logout();
                        $message = lang('notify.130');
                        $this->utils->debug_log('Post player login Iovation response error', $iovationResponse);

                        $adminUserId = Transactions::ADMIN;
                        $tagName = 'Iovation Denied - Player Login';
                        $tagId = $this->player_model->getTagIdByTagName($tagName);

                        if (empty($tagId)) {
                            $tagId = $this->player_model->createNewTags($tagName, $adminUserId);
                        }

                        $this->player_model->addTagToPlayer($player->playerId, $tagId, $adminUserId);

                        if ($this->utils->is_mobile()) {
                            $message = json_encode($message);
                        } else {
                            $this->alertMessage(BaseController::MESSAGE_TYPE_ERROR, $message);
                        }

                        if ($this->input->is_ajax_request()) {
                            $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
                            return;
                        }

                        if (empty($message)) $this->session->unset_userdata('result');

                        redirect('/iframe/auth/login');
                    }
                }

				$this->utils->debug_log(__METHOD__,'============================login_by_password',$username);
				$result = $this->player_library->login_by_password($username, $password, $remember_me);

				// if ($this->utils->getConfig('enable_player_login_report')) {
				// 	$this->load->model('player_login_report');
				// 	$this->utils->savePlayerLoginDetails($player->playerId, $username, $result, Player_login_report::LOGIN_FROM_PLAYER);
				// 	$this->utils->debug_log(__METHOD__,'savePlayerLoginDetails', $result);
				// }

				if($gamePlatform){
	            	$success = $result['success'];
	            	# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
			        if ($this->utils->setNotActiveOrMaintenance($gamePlatform)) {
			            $success = false;
			        }
			        if($success){
			        	if($gamePlatform == GGPOKER_GAME_API){
				        	return $gameLoginResult =  $this->custom_ggpoker_login($gamePlatform,$redirectUri);
						}
						if($gamePlatform == ONEWORKS_API){
				        	return $this->oneworks_app_auth($gamePlatform);
				        }
			        }

	                $this->returnJsonResult(array('success' => $success));
	                return;
	            }

                if(!$result['success']){
                    throw new Exception(json_encode($result['errors']));
                }else{
                    // login success
                    $player_id = $result['player_id'];
                    $source_method = __METHOD__;
                    $this->player_library->triggerPlayerLoggedInEvent( $player_id, $source_method);
                }

                if($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->load->library('fast_track');
                    $this->fast_track->login($player->playerId);
                }

                $url = $this->input->post('referer');
                $session_redirect = $this->session->userdata('referrer');
                if(!empty($session_redirect)) {
                    $url = $this->session->userdata('referrer');
                    //remove referrer from session
                    $this->session->unset_userdata('referrer');
                }

                if( $this->input->is_ajax_request() && $this->utils->is_mobile() ){
					$this->returnJsonResult(array('status' => 'success', 'msg' => '', 'redirect_url' => $url));
					return;
				}

				#Switch case for redirect url
				if(!empty($url)){
					switch ($url) {
						case strpos($url, 'sports')   !== FALSE:
							break;

						case strpos($url, 'logout')   !== FALSE:
						case strpos($url, 'login')    !== FALSE:
						case strpos($url, 'register') !== FALSE || strpos($url, 'postRegisterPlayer') !== FALSE:
							$url = $this->utils->getPlayerHomeUrl();
							break;

						case strpos($url, 'autoDeposit3rdParty') !== FALSE:
						    $url = $this->utils->getPlayerDepositUrl();
							break;

						default:
							break;
					}
				}
				$this->utils->debug_log('url', $url);

                if($this->utils->getConfig('redirect_to_player_center_if_from_www') && (empty($url) || $url == $this->utils->getSystemUrl('www') . '/')) {
                    $url = $this->utils->getPlayerHomeUrl();
                    $this->utils->debug_log('override url by config redirect_to_player_center_if_from_www', $url);
                }

				$iniframe = $this->input->post('iniframe');
				if($iniframe){
					$rlt=['success'=> true, 'act'=>'redirect_url', 'url'=>$url];

					$this->load->view('player/iframe_callback', [
						'origin' => '*',
						'result' => $this->utils->encodeJson($rlt),
					]);
					return;
				}

				if ($this->utils->getConfig('show_email_verification_on_login') && $this->utils->isEmailVerifyEnabled($player->playerId) && empty($player->lastLoginTime) && !empty($player->email)) {
					$this->goActiveAccount($player->playerId);
				}
				return redirect($url);
			} catch (Exception $e) {
				$messages = json_decode($e->getMessage(), true);

				if( $this->input->is_ajax_request() ){
					$this->returnJsonResult(array('status' => 'error', 'msg' => $e->getMessage()));
					return;
				}

				if(isset($messages['username'])){
        			$data['username_error'] = $messages['username'];

        		} elseif(isset($messages['password'])){
        			$data['password_error'] = $messages['password'];

        		} elseif (!empty($messages)) {
					if (is_array($messages)) {
						foreach ($messages as $key => $message) {
							$snackbar[$key] = $message;
						}
					} else {
						$snackbar[] = $messages;
					}
				}

			}
		}

        if(!empty($this->input->get('referrer'))) {
            $url = $this->input->get('referrer');
            $this->session->set_userdata('referrer', $url);
        }

		$username = '';
		$password_holder = '';
		$remember_me = '';

		$token = $this->input->cookie('remember_me');
		$this->utils->debug_log('auth-login', 'remember_me-token', $token);
		if (!empty($token)) {
			$this->load->model('player_login_token');
			$player_id = $this->player_login_token->getPlayerId($token);
			$username = $this->player_model->getUsernameById($player_id);
			$remember_me = 1;
			$password_holder = $this->session->userdata('password_holder');

			if(empty($password_holder)){
					$password_holder = $this->utils->generateRandomCode(14);
					$this->session->set_userdata('password_holder', $password_holder);
			}
		}

		$this->utils->debug_log('auth-login remember_me username', $username, $token, $password_holder);

		$data['username'] = isset($username) ? $username : '';
		$data['password_holder'] = isset($password_holder) ? $password_holder : '';
		$data['remember_me'] = isset($remember_me) ? $remember_me : '';

		$data['snackbar'] = isset($snackbar) ? $snackbar : [];
		$data['currentLang'] = $this->language_function->getCurrentLanguage();
        $data['forget_password_enabled'] = $this->utils->checkForgetPasswordEnabled();
		$data['login_captcha_enabled'] = $this->operatorglobalsettings->getSettingJson('login_captcha_enabled');
		$data['remember_password_enabled'] = $this->operatorglobalsettings->getSettingJson('remember_password_enabled');
        $data['_csrf_hidden_field'] = $this->_initCSRFAndReturnHiddenField();

		$referer_page=$this->input->post('referer');
        if(empty($referer_page)){
	        $referer_page = $this->session->flashdata('REDIRECT_SOURCE');
			if(empty($referer_page)){
            	$referer_page = (empty($_SERVER['HTTP_REFERER'])) ? NULL : $_SERVER['HTTP_REFERER'];
        	}
        }
        $data['referer_page'] = $referer_page;

        $data['append_ole777id_js_content'] = null;
        $data['append_ole777id_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'robot', 'head');
        if(!empty($data['append_ole777id_js_filepath']['login'])){
            $data['append_ole777id_js_content'] = $data['append_ole777id_js_filepath']['login'];
        }

		$this->session->set_userdata('currentLanguage', $this->language_function->getCurrentLanguage());
		$this->_load_template(lang('lang.login_title'), $this->utils->getPlayerCenterMobileLoginFile() , $data);
	} // EOF login

	/**
	 * registered from LINE
	 *	This method will store form data into "third_party_login.extra_info" .
	 *
	 * @return void
	 */
	public function line_register(){
		$line_reg = $this->input->get_post('line_reg');
		$_post=$this->input->post();
		$pre_register_form = [];
		$extra_info = [];

		if( ! empty($_post['line_reg']) ){
			$pre_register_form = array_merge($pre_register_form, $_post);
		}

		$this->line_login($extra_info, $pre_register_form);
	}

    public function line_login($extra_info = [], $pre_register_form = []){

		$this->utils->debug_log('===========pre_register_form', $pre_register_form);

        if($this->authentication->isLoggedIn() && $this->input->get('type') != 'bind'){
            $this->goPlayerHome('home');
        }

        if(!$this->utils->getConfig('line_credential')){
            $this->goPlayerLogin();
        }

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        $this->load->model('third_party_login');
        $this->CI->load->helper('string');
        $line_credential = $this->utils->getConfig('line_credential');
        $uuid = uniqid('line_');
        $ip = $this->utils->getIP();
        $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;

        $extra_info['btag']                       = $this->input->get('btag')?: $this->utils->getBtagCookie();
        $extra_info['tracking_code']              = $this->input->get('tracking_code')?: $this->getTrackingCode();
        $extra_info['tracking_source_code']       = $this->input->get('tracking_source_code')?: $this->getTrackingSourceCode();
        $extra_info['agent_tracking_code']        = $this->input->get('agent_tracking_code')?: $this->getAgentTrackingCode();
        $extra_info['agent_tracking_source_code'] = $this->input->get('agent_tracking_source_code')?: $this->getAgentTrackingSourceCode();
		$extra_info['invitationCode']             = $this->input->get('referral_code')?: $this->utils->getReferralCodeCookie();

		if (!empty($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $query_params);
            if (isset($query_params['tracking_code'])) {
                $extra_info['tracking_code'] = $query_params['tracking_code'];
            }
            if (isset($query_params['tracking_source_code'])) {
                $extra_info['tracking_source_code'] = $query_params['tracking_source_code'];
			}
			if (isset($query_params['agent_tracking_code'])) {
                $extra_info['agent_tracking_code'] = $query_params['agent_tracking_code'];
            }
			if (isset($query_params['agent_tracking_source_code'])) {
                $extra_info['agent_tracking_source_code'] = $query_params['agent_tracking_source_code'];
            }
			if (isset($query_params['referral_code'])) {
                $extra_info['invitationCode'] = $query_params['referral_code'];
            }
		}

        $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), json_encode($pre_register_form));

		$redirect_uri = $line_credential['redirect_uri'];
		$currDomain = $this->utils->getHttpHost();
		$redirect_uri = sprintf($redirect_uri, $currDomain);

		$state_str = "";
		$playerId = "";
		$sign = "";

		if($this->input->get('type') == 'bind' && $pre_register_form != []){
			$playerId = $this->authentication->getPlayerId();
			$playerinfo = $this->CI->player_model->getPlayerById($playerId);
			$data = [
				'playerId' => $playerId,
				'username' => $playerinfo->username
			];
			$sign = $this->sign($data);
			$state_str = '|0|'.$playerId.'|'.$sign.'|'.$this->input->get('type');

			$goto_url = isset($pre_register_form['goto_url']) ? $pre_register_form['goto_url'] : '';
			if($goto_url != ''){
				$state_str = $state_str.'|'.$goto_url;
			}

		}else if($this->input->get('type') == 'bind'){
			$playerId = $this->authentication->getPlayerId();
			$playerinfo = $this->CI->player_model->getPlayerById($playerId);
			$data = [
				'playerId' => $playerId,
				'username' => $playerinfo->username
			];
			$sign = $this->sign($data);
			$state_str = '|1|'.$playerId.'|'.$sign.'|'.$this->input->get('type');

		}else if($pre_register_form != []){
			$state_str =  '|2';
			$goto_url = isset($pre_register_form['goto_url']) ? $pre_register_form['goto_url'] : '';
			if($goto_url != ''){
				$state_str =  $state_str.'|'.$goto_url;
			}
		}else{
			$state_str = '|3';
		}

        $line_login = [
            'response_type' => 'code',
            'client_id'     => $line_credential['client_id'],
            'redirect_uri'  => $redirect_uri,
            'state'         => $uuid. $state_str,
            'scope'         => 'profile openid email',
            // 'prompt'        => 'consent' //can delete later
			// 'playerId'		=> $playerId,
			// 'sign'			=> $sign
        ];
		$this->utils->debug_log('==========line_login', $line_login);

        if($this->utils->getConfig('enabled_line_add_friend_after_line_login')){
            $line_login['bot_prompt'] = 'aggressive';
        }

        $url = $line_credential['auth_url'].'?'.http_build_query($line_login, '', '&', PHP_QUERY_RFC3986);
        redirect($url);
    }

	// ole sso/auth/api
	public function ole_login($extra_info = [], $pre_register_form = []){

		// ole sso -> logout
		if($this->input->get('redirect_uri')){
			if($this->authentication->isLoggedIn()){
				$this->authentication->logout();
			}
			if(!$this->utils->getConfig('ole_sso_setting')){
				$this->goPlayerLogin();
			}
		}
		// ole auth / api
		else{
			if($this->authentication->isLoggedIn()){
				$this->goPlayerHome('home');
			}

			if(!$this->utils->getConfig('ole_credential')){
				$this->goPlayerLogin();
			}
		}

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }

        $this->load->model('third_party_login');
        $this->CI->load->helper('string');
        $ip = $this->utils->getIP();
        $status = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;

        $extra_info['btag']                       = $this->input->get('btag')?: $this->utils->getBtagCookie();
        $extra_info['tracking_code']              = $this->input->get('tracking_code')?: $this->getTrackingCode();
        $extra_info['tracking_source_code']       = $this->input->get('tracking_source_code')?: $this->getTrackingSourceCode();
        $extra_info['agent_tracking_code']        = $this->input->get('agent_tracking_code')?: $this->getAgentTrackingCode();
        $extra_info['agent_tracking_source_code'] = $this->input->get('agent_tracking_source_code')?: $this->getAgentTrackingSourceCode();
		$extra_info['invitationCode']             = $this->input->get('referral_code')?: $this->utils->getReferralCodeCookie();

		if (!empty($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $query_params);
            if (isset($query_params['tracking_code'])) {
                $extra_info['tracking_code'] = $query_params['tracking_code'];
            }
            if (isset($query_params['tracking_source_code'])) {
                $extra_info['tracking_source_code'] = $query_params['tracking_source_code'];
			}
			if (isset($query_params['agent_tracking_code'])) {
                $extra_info['agent_tracking_code'] = $query_params['agent_tracking_code'];
            }
			if (isset($query_params['agent_tracking_source_code'])) {
                $extra_info['agent_tracking_source_code'] = $query_params['agent_tracking_source_code'];
            }
			if (isset($query_params['referral_code'])) {
                $extra_info['invitationCode'] = $query_params['referral_code'];
            }
		}
		if($this->input->get('redirect_uri')){
			$ole_sso_setting = $this->utils->getConfig('ole_sso_setting');
        	$uuid = uniqid('ole_sso_');
			$redirect_uri = $this->input->get('redirect_uri');
			$currDomain = $this->utils->getHttpHost();
			$redirect_uri = sprintf($redirect_uri, $currDomain);
			$ole_login = [
				'redirect_uri'  => $redirect_uri,
				'client_id'     => $ole_sso_setting['client_id'],
				'secret_key'	=> $ole_sso_setting['secret_key'],
				'state'			=> $uuid,
			];
		}else {
			$ole_credential = $this->utils->getConfig('ole_credential');
			$uuid = uniqid('ole_auth_');
			$redirect_uri = $ole_credential['redirect_uri'];
			$currDomain = $this->utils->getHttpHost();
			$redirect_uri = sprintf($redirect_uri, $currDomain);
			$ole_login = [
				'redirect_uri'  => $redirect_uri
			];
			if($this->input->get('goto_url')){
				$ole_login['goto_url'] = $this->input->get('goto_url');
			}
		}
		$this->utils->debug_log('==========ole_login', $ole_login);
        $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), json_encode($pre_register_form));


		if($this->input->get('redirect_uri')){
			$url = $ole_sso_setting['auth_url'].'?sso_login=true&'.http_build_query($ole_login, '', '&', PHP_QUERY_RFC3986);
		}else if($this->input->get('goto_url')){
			$url = $ole_credential['auth_url'].'?'.http_build_query($ole_login, '', '?', PHP_QUERY_RFC3986);
		}else{
			$url = $ole_credential['auth_url'].'?'.http_build_query($ole_login, '', '&', PHP_QUERY_RFC3986);
		}
        redirect($url);
	}

	public function custom_ggpoker_login($gamePlatformId, $redirectUri){
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		$playerId = $this->authentication->getPlayerId();
		$playerName = $this->authentication->getUsername();
		$code = $api->getExternalAccountIdByPlayerUsername($playerName);
		if(!empty($code)){
			$redirectUri .= "?code=".$code;
			return $this->returnJsonResult(array(
					'success' => true,
					'redirectUri' => $redirectUri
				)
			);
			// return redirect($redirectUri);
		}
		return $this->returnJsonResult(array('success' => false));
	}

	public function oneworks_app_auth($gamePlatformId) {
		$this->load->model(['country_rules']);
		$ip = $this->utils->getIP();
		$isSiteBlock = $this->country_rules->getBlockedStatus($ip, 'blocked_www_m');
		if($isSiteBlock){
			return $this->returnJsonResult(array("success"=> false, "msg" => lang("Sorry, the IP you are using is not in the current service area.")));
		}
		# LOAD GAME API
		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		$playerName = $this->authentication->getUsername();
		$playerId = $this->authentication->getPlayerId();

		$this->load->model('player_model');
		$password = $this->CI->player_model->getPasswordById($playerId);

		# CHECK PLAYER IF EXIST
		$playerExistResponse = $api->isPlayerExist($playerName);

		# IF NOT CREATE PLAYER
		if (isset($playerExistResponse['exists']) && !$playerExistResponse['exists'] && $playerExistResponse['success']==true) {
			if(!is_null($playerExistResponse['exists'])){
				$extra['ip']=$this->utils->getIP();
				# CREATE PLAYER
				$createResponse = $api->createPlayer($playerName, $playerId, $password, NULL, $extra);
				$this->utils->debug_log('CREATEPLAYERONGAMEPLATFORM PLAYER =====================>['.$gamePlatformId.']:',$createResponse);
				if ($createResponse['success']) {
					$api->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
				}
			}
		}

		$result = $api->getMobileAppToken($playerName);
		if(!isset($result['msg'])){
			$result['msg'] = lang("Request token failed! Please try again.");
		}
		return $this->returnJsonResult($result);
	}

	public function login_by_mobile() {
		$this->load->model(array('operatorglobalsettings', 'country_rules'));
		$this->load->library('user_agent');

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }
		// $ip = $this->input->ip_address(); // '180.232.133.50'; PHILIPPINES

		// $isSiteBlock = $this->country_rules->getBlockedStatus($ip, 'blocked_www_m');

		// if($isSiteBlock) {
		// 	$urlBlockPage = $this->utils->getConfig('common_block_page_url');
		// 	if(empty($urlBlockPage)) {
		// 		return $this->returnErrorStatus(403, true);
		// 	} else {
		// 		return redirect($urlBlockPage);
		// 	}
		// }

		$template = $this->utils->getPlayerCenterTemplate();
		$data['currentLang'] = $this->language_function->getCurrentLanguage();
		$this->_load_template(lang('lang.login_title'), $template . '/auth/login_by_mobile', $data);
	}

	public function login_game_platform($lang = 'cs', $gamePlatform = ONEWORKS_API) {


		if($gamePlatform == GAMESOS_API){
			$this->getPlatformTicketLogin($lang, $gamePlatform);
			return;
		}


		$lang == 'cs' ? $this->session->set_userdata('currentLanguage', 'ch') : $this->session->set_userdata('currentLanguage', 'en');




		$this->load->model('operatorglobalsettings');
		if (!$this->authentication->getPlayerId()) {

			if ($this->input->post()) {
				if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled')) {
					$this->form_validation->set_rules('login_captcha', lang('label.captcha'), 'callback_check_captcha');
				}
				$username = $this->input->post('username');
				try {
					$this->form_validation->set_rules('referer', 'lang:referer', 'trim|xss_clean');
					$this->form_validation->set_rules('username', 'lang:username', 'trim|required|xss_clean');
					$this->form_validation->set_rules('password', 'lang:password', 'trim|required|xss_clean');

					if (!$this->form_validation->run()) {
						throw new Exception(json_encode(validation_errors()));
					}

					$password = $this->input->post('password');

					$this->load->model(array('player_model', 'http_request'));
					$playerId = $this->player_model->getPlayerIdByUsername($username);
					$isBlocked = $this->player_model->isBlocked($playerId);
					if ($isBlocked) {
						throw new Exception(json_encode(array('blocked' => lang('player.blocked'))));
					}

					if (!$this->authentication->login($username, $password)) {
						throw new Exception(json_encode($this->authentication->get_error_message()));
					}

					$playerId = $this->authentication->getPlayerId();

					$this->saveHttpRequest($playerId, Http_request::TYPE_LAST_LOGIN);
					$this->goGamePlatform($gamePlatform, $username, $lang);
				} catch (Exception $e) {

					$messages = json_decode($e->getMessage(), true);
					if (!empty($messages)) {
						if (is_array($messages)) {
							foreach ($messages as $key => $message) {
								$snackbar[] = $message; # TODO(KAISER): lang('error.login_failed.' . $e->getCode());
							}
						} else {
							$snackbar[] = $messages;
						}
					}
				}
			}

			$data['snackbar'] = isset($snackbar) ? $snackbar : [];
			$data['currentLang'] = $this->language_function->getCurrentLanguage();
			$data['lang'] = $lang;

			switch ($gamePlatform) {

				case ONEWORKS_API:
					$this->_load_template(lang('lang.login_title'), $this->utils->getPlayerCenterTemplate() . '/auth/login_ow_mobile', $data);
				break;

				case GAMESOS_API:

					$lang = strtolower($lang);
					if($lang == 'cs' || $lang == 'zh' ){
						$this->language_function->setCurrentLanguage(2);
						$this->lang->is_loaded = array();
						$this->lang->language = array();
						$this->lang->load('main', 'chinese');

					}else{
						$this->language_function->setCurrentLanguage(1);
						$this->lang->is_loaded = array();
						$this->lang->language = array();
						$this->lang->load('main', 'english');
					}
					$data['currentLang'] = $this->language_function->getCurrentLanguage();
				    $data['gamePlatform'] = $gamePlatform;
				  // $this->_load_template(lang('lang.login_title'), $this->utils->getPlayerCenterTemplate() . '/auth/login_gamesos_desktop', $data);
				    $this->load->view($this->utils->getPlayerCenterTemplate() . '/auth/login_gamesos_desktop', $data);

				break;
			}
		} else {
			$username = $this->authentication->getUsername();
			$this->goGamePlatform($gamePlatform, $username, $lang);
		}
	}

	public function getPlatformTicketLogin($lang = en , $gamePlatform = GAMESOS_API){
        $this->load->model('operatorglobalsettings');
		if ($this->input->post()) {
			if ($this->operatorglobalsettings->getSettingJson('login_captcha_enabled')) {
				$this->form_validation->set_rules('login_captcha', lang('label.captcha'), 'callback_check_captcha');
			}

			$username = $this->input->post('username');
			try {
				$this->form_validation->set_rules('referer', 'lang:referer', 'trim|xss_clean');
				$this->form_validation->set_rules('username', 'lang:username', 'trim|required|xss_clean');
				$this->form_validation->set_rules('password', 'lang:password', 'trim|required|xss_clean');

				if (!$this->form_validation->run()) {
					throw new Exception(json_encode(validation_errors()));
				}

				$password = $this->input->post('password');

				$this->load->model(array('player_model', 'http_request'));
				$playerId = $this->player_model->getPlayerIdByUsername($username);
				$isBlocked = $this->player_model->isBlocked($playerId);
				if ($isBlocked) {
					throw new Exception(json_encode(array('blocked' => lang('player.blocked'))));
				}

				if (!$this->authentication->login($username, $password)) {
					throw new Exception(json_encode($this->authentication->get_error_message()));
				}

				$playerId = $this->authentication->getPlayerId();

				$this->saveHttpRequest($playerId, Http_request::TYPE_LAST_LOGIN);
				$this->goGamePlatform($gamePlatform, $username, $lang);
			} catch (Exception $e) {

				$messages = json_decode($e->getMessage(), true);
				if (!empty($messages)) {
					if (is_array($messages)) {
						foreach ($messages as $key => $message) {
							$snackbar[] = $message; # TODO(KAISER): lang('error.login_failed.' . $e->getCode());
						}
					} else {
						$snackbar[] = $messages;
					}
				}
				$data['snackbar'] = isset($snackbar) ? $snackbar : [];
				$data['lang'] = $lang;
				$data['currentLang'] = $this->language_function->getCurrentLanguage();
			    $data['gamePlatform'] = $gamePlatform;

			    $this->load->view($this->utils->getPlayerCenterTemplate() . '/auth/login_gamesos_desktop', $data);
			}
		} else {
			$data['snackbar'] = isset($snackbar) ? $snackbar : [];
			$data['currentLang'] = $this->language_function->getCurrentLanguage();
			$data['lang'] = $lang;

			$lang = strtolower($lang);
			if($lang == 'cs' || $lang == 'zh' ){
				$this->language_function->setCurrentLanguage(2);
				$this->lang->is_loaded = array();
				$this->lang->language = array();
				$this->lang->load('main', 'chinese');

			}else{
				$this->language_function->setCurrentLanguage(1);
				$this->lang->is_loaded = array();
				$this->lang->language = array();
				$this->lang->load('main', 'english');
			}
			$data['currentLang'] = $this->language_function->getCurrentLanguage();
		    $data['gamePlatform'] = $gamePlatform;
		    $this->load->view($this->utils->getPlayerCenterTemplate() . '/auth/login_gamesos_desktop', $data);
		}
	}

	private function goGamePlatform($gamePlatform, $username, $lang) {

		switch ($gamePlatform) {

			case ONEWORKS_API:

			$gameUrl = $this->getGameApiUrl($gamePlatform, $username, $lang);
			if (!empty($gameUrl)) {
				$this->utils->verbose_log('LOGIN GAMEPLATFORM ----------------------> ', $gameUrl);
				$this->utils->verbose_log('LOGIN GAMEPLATFORM ENCODED URL ----------------------> ', urlencode($gameUrl));
			// redirect($gameUrl);
				redirect($gameUrl);
			} else {
				$messages = lang("error.login_failed");
				$snackbar[] = $messages;
				$this->mobileLoginFailed($snackbar, $gamePlatform);
				return;
			}
			break;

    		case GAMESOS_API:

    			$api = $this->utils->loadExternalSystemLibObject($gamePlatform);

    			$rlt = $api->queryForwardGame($username, array('desktop_client' => true ));
    			if($rlt['success']){
    				$this->session->set_userdata('gamesos_ticket', $rlt['ticket']);
    				redirect('player_center/loadDesktopGame/'.$gamePlatform.'/'.$username);
    	    	} else {
				$messages = lang("error.login_failed");
				$snackbar[] = $messages;
				$this->mobileLoginFailed($snackbar, $gamePlatform);
				return;
			}

			break;

		}
	}

	private function getGameApiUrl($gamePlatformId, $username, $lang) {
		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
		$currentLang = $this->language_function->getCurrentLanguage();
		if ($lang) {
			$language = $lang;
		} else {
			$language = ($currentLang == '1') ? 'en' : 'cs';
		}
		$extra = array('gameType' => 1, 'platform' => 'mobile', 'language' => $language);
		$rlt = $api->queryForwardGame($username, $extra);
		$this->utils->verbose_log('LOGIN GETGAMEAPIURL ----------------------> ', $rlt);
		if (!empty($rlt)) {
			if ($rlt['success']) {
				return $rlt['url'];
			}
		}
		return false;
	}

	private function mobileLoginFailed($snackbar, $gamePlatform) {
		$data['snackbar'] = isset($snackbar) ? $snackbar : [];
		$data['currentLang'] = $this->language_function->getCurrentLanguage();

		switch ($gamePlatform) {
				case ONEWORKS_API:
					$this->_load_template(lang('lang.login_title'), $this->utils->getPlayerCenterTemplate() . '/auth/login_ow_mobile', $data);
				break;
				case GAMESOS_API:
					$this->_load_template(lang('lang.login_title'), $this->utils->getPlayerCenterTemplate() . '/auth/login_gamesos_desktop', $data);
				break;
			}
	}

	public function logout() {
        $result = $this->authentication->logout();

        if ($this->input->is_ajax_request()) {
            return;
        }

        redirect($result['redirect_url']);
	}

	################################################ PRIVATE FUNCTIONS ################################################

	private function _load_template($title, $view, $data = null, $activenav = '', $description = '', $keywords = '') {
		$this->template->set_template($this->utils->getPlayerCenterTemplate(false));
		$this->template->append_function_title($title);

		# scripts that was originally written in template
		$base_path = base_url() . $this->utils->getPlayerCenterTemplate();
		$this->template->add_js('/resources/third_party/jquery-wizard/0.0.7/jquery.wizard.min.js');

        if (isset($data['is_enabled_iovation_in_player_login']) && $data['is_enabled_iovation_in_player_login']) {
            if ($this->utils->getConfig('iovation')['use_first_party']) {
                $this->template->add_js($this->utils->jsUrl($this->utils->getConfig('iovation')['first_party_js_config']));
            } else {
                $this->template->add_js($this->utils->jsUrl('config.js'));
            }

            $this->template->add_js($this->utils->jsUrl('iovation.js'));
        }

		// $this->template->set_template('iframe');
		$this->template->write('skin', 'template1.css');
		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);
		$this->template->write_view('main_content', $view, $data);
		// $this->template->write_view('main_content', 'template/deposit_template', [
		// 	'title'				=> $title,
		// 	'deposit_view' 		=> $view,
		// 	'deposit_view_data' => $data,
		// ]);
		$this->template->render();
	}

	public function login_by_token($next, $platformId, $username = null, $token = null) {
		$get = $this->input->get();
		if ($get && (isset($get['username']) && isset($get['ticket']))) {
			$username = $get['username'];
			$token = $get['ticket'];
		}
		$this->CI->utils->debug_log('login_by_token on auth --- platformId == '. $platformId);
		$this->utils->verbose_log('Get Params: ', $username, ' token: ', $token);

        $this->load->library(['player_library']);

        $result = $this->player_library->login_from_game($platformId, $token);

        if($result['success']){
			if ($next == "redirect_url") {
                redirect($this->utils->getSystemUrl('player') . ((empty($get['redirect_url'])) ? '/' : $get['redirect_url']));
            } else {
                redirect($this->goPlayerHome());
            }
        }else{
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, (is_array($result['errors']) && !empty($result['errors'])) ? end($result['errors']) : $result['errors']);
            // show_error('invalid token', 403);
            redirect($this->goPlayerHome());
        }
	}

    public function captcha($namespace = 'default', $width = null, $height = null) {
        // $this->load->library('session');
        // $this->load->config('csecurimage');
        $active = $this->config->item('si_active');
		$current_host = $this->utils->getHttpHost();
		$active_domain_assignment = $this->config->item('si_active_domain_assignment');
		if( ! empty($active_domain_assignment[$current_host]) ){
			$active = $active_domain_assignment[$current_host];
		}
        $allsettings = array_merge( $this->config->item('si_general'), $this->config->item($active), ['namespace' => $namespace]);

		if ($width) {
			$allsettings['image_width'] = $width;
		}

		if ($height) {
			$allsettings['image_height'] = $height;
		}

        $this->load->library('captcha/securimage');
        $img = new Securimage($allsettings);

        //$img->captcha_type = Securimage::SI_CAPTCHA_MATHEMATIC;

        // $_SESSION['auth_flag']='test';
        $this->utils->verbose_log('generate captcha session');

        $img->show($this->config->item('si_background'));

        //try get code
        //save to session
        // $this->utils->verbose_log('captcha session', $this->session->all_userdata());

    }

	public function smsCaptcha($width=null, $height=null) {

		$active = $this->config->item('si_active');
		$current_host = $this->utils->getHttpHost();
		$active_domain_assignment = $this->config->item('si_active_domain_assignment');
		if( ! empty($active_domain_assignment[$current_host]) ){
			$active = $active_domain_assignment[$current_host];
		}
		$allsettings = array_merge($this->config->item('si_general'), $this->config->item($active));

		if ($width) {
			$allsettings['image_width'] = $width;
		}

		if ($height) {
			$allsettings['image_height'] = $height;
		}

		$this->load->library('captcha/sms_securimage');
		$img = new Sms_securimage($allsettings);

		$this->utils->verbose_log('generate captcha session');

		$img->show($this->config->item('si_background'));
	}

	public function login_from_admin($adminToken, $playerId) {
		$currencyKey=$this->input->get(Multiple_db::__OG_TARGET_DB);
		if( empty($currencyKey) ){
			$currencyKey = null; // FALSE -> null, for default param of validateAdminToken().
		}
		//validate adminToken
		if ($this->validateAdminToken($adminToken, 'login_as_player', $currencyKey)) {
			$this->load->model(array('player_model', 'http_request'));
			$row = $this->player_model->getPlayerById($playerId);
			$password = $this->utils->decodePassword($row->password);

			$allow_clear_session=false;
			$login_from_admin = $this->authentication->login_from_admin($row->username, $password, $allow_clear_session);

			$playerId = $this->authentication->getPlayerId();
			$this->player_model->updatePlayerOnlineStatus($playerId, Player_model::PLAYER_ONLINE);

			if( ! empty($currencyKey) ){
				$this->load->model(['multiple_db_model']);
				$insertOnly = false;
				$this->multiple_db_model->syncPlayerFromCurrentToOtherMDB($playerId, $insertOnly);
			}

			$this->saveHttpRequest($playerId, Http_request::TYPE_LAST_LOGIN);

			if ($this->utils->getConfig('enable_player_login_report')) {
				$this->load->model('player_login_report');
				$result['success'] = $login_from_admin;
				$this->utils->savePlayerLoginDetails($playerId, $row->username, $result, Player_login_report::LOGIN_FROM_ADMIN);
				$this->utils->debug_log(__METHOD__,'savePlayerLoginDetails', $result);
			}

			return redirect('/');
		}
		//go to login
		redirect('/iframe/auth/login');
	}

	/**
	 * login_with_token wrapper, OGP-13552, workaround for Ole777 webapp game launch
	 * Usage: /iframe/auth/launch_with_token/(token)?next=(game_url)
	 * @param	string	$token		Player's effective token
	 * @uses	string	GET:next	URL to go on successful login
	 * @uses	string	GET:fail	URL to go on login failure
	 * @return	none
	 */
	public function launch_with_token($token) {
		// If a player is currently logged in
		if ($this->authentication->isLoggedIn()) {
			$logged_token = $this->authentication->getPlayerToken();
			$this->utils->debug_log(__METHOD__, 'checking token', [ 'token' => $token, 'logged_token' => $logged_token ]);
			// If current player's token == provided token, simply redirect to game url
			if ($logged_token == $token) {
				$url_next = $this->input->get('next');
				$this->utils->debug_log(__METHOD__, 'token matches', 'redirecting to game', $url_next);
				redirect($url_next);
				return;
			}
			// If token mismatches, logout, then continue with the rest of flow
			else {
				$this->utils->debug_log(__METHOD__, 'token mismatch', 'force-logout for safe concern');
				$this->authentication->logout();
			}
		}

		// Redirect to login_with_token
		$redirect_target = "/iframe/auth/login_with_token/{$token}/custom?{$this->input->server('QUERY_STRING')}";
		$this->utils->debug_log(__METHOD__, 'not logged in', 'redirecting to login_with_token', $redirect_target);
		redirect($redirect_target);

		return;
	}

	/**
	 * Generic endpoint for login with token
	 *
	 * @param	string	$token					Player's effective token
	 * @param	string	$custom_redirect		'custom' to redirect to custom URL when login fails; otherwise redirect to player center home.  Defaults to null.
	 * @uses	string	GET:next				URL to go on successful login
	 * @uses	string	GET:return_home_domain	Will be stored as cookie for game launcher to use
	 * @uses	string	GET:fail				URL to go on login failure
	 *
	 * @return	none
	 */
	public function login_with_token($token, $custom_redirect = null) {
		$cookie_lifetime_rhd_default = 60 * 10;

		$next = $this->input->get('next');
		$return_home_domain = $this->input->get('return_home_domain');
		$fail_url = $this->input->get('fail') ?: $this->utils->getConfig('login_with_token_fail_url');

		$this->utils->debug_log('token', $token, 'next', $next, 'return_home_domain', $return_home_domain);

		$this->load->library(['player_library']);

		$result = $this->player_library->login_by_token($token);
		$this->utils->debug_log('login_with_token - result: ', json_encode($result));
		if($result['success']){
			$this->load->helper('cookie');
			$cookie_lifetime_rhd = $this->utils->getConfig('cookie_lifetime_return_home_domain') ?: $cookie_lifetime_rhd_default;
			set_cookie('return_home_domain', $return_home_domain, $cookie_lifetime_rhd);
			if (!empty($next)) {
				//decode
				$next=urldecode($next);
				$this->utils->debug_log('after decode next url', $next);
				redirect($next);
			} else {
				redirect($this->goPlayerHome());
			}
		}else{

			if (empty($fail_url) || $custom_redirect != 'custom') {
			    $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, (is_array($result['errors']) && !empty($result['errors'])) ? end($result['errors']) : $result['errors']);
				// show_error('invalid token', 403);
            	redirect($this->goPlayerHome());
            }
            else {
            	redirect($fail_url);
            }
		}
	}

	public function login_with_token_ggpoker() {
		$token = $this->input->get('token');
		$this->utils->debug_log('GGPOKER_GAME_API - CASHIER TOKEN: ', $token);
		if (empty($token)) {
			$this->utils->debug_log('GGPOKER_GAME_API - CASHIER EMPTY TOKEN: ');
			redirect('/');
		}

		$redirect_url = "/iframe/auth/login_with_token/{$token}?next=/player_center/mobile_transfer_view";
		$this->utils->debug_log('GGPOKER_GAME_API - REDIRECT: ', $redirect_url);
		redirect($redirect_url);
	}

	/**
	 * only for login
	 * @param  string $jsonp true or false
	 * @return jsonp or json
	 */
	public function change_active_currency($jsonp='true'){
		$jsonp=$jsonp=='true';
		//make sure we set session
		$this->load->library(['session']);

		$currencyKey=$this->input->get(Multiple_db::__OG_TARGET_DB);
		$result=['success'=>false];
		//validate currency, no super
		if($this->utils->isAvailableCurrencyKey($currencyKey)){
			$_multiple_db=Multiple_db::getSingletonInstance();
			$_multiple_db->init($currencyKey);
			$_multiple_db->rememberActiveTargetDB();

			$result['success']=true;
		}else{
			$result['message']=lang('not available currency');
		}

		if($jsonp){
			$this->returnJsonpResult($result);
		}else{
			$this->returnJsonResult($result);
		}
	}

	/**
	 * SSO over MDB
	 * @param  string $currencyKey
	 * @param  string $jsonp true or false
	 *
	 */
	public function change_active_currency_for_logged($currencyKey, $jsonp='true') {
		$jsonp=$jsonp=='true';
		$result=['success'=>false];
		$this->load->library(['authentication']);
		//still old db
		$loggedPlayerId=$this->authentication->getPlayerId();
		$loggedUsername=$this->authentication->getUsername();

		$this->utils->debug_log('loggedPlayerId', $loggedPlayerId, 'loggedUsername', $loggedUsername, 'ci db', $this->db->getOgTargetDB());
		if(empty($loggedPlayerId)){
			$result['message']=lang('session timeout, please relogin');
			return $this->returnJsonResult($result);
		}

		// $currencyKey=$this->input->get(Multiple_db::__OG_TARGET_DB);
		//validate currency, no super
		if($this->utils->isAvailableCurrencyKey($currencyKey)){
			$_multiple_db=Multiple_db::getSingletonInstance();
			$_multiple_db->switchCIDatabase($currencyKey);
			// $_multiple_db->rememberActiveTargetDB();
			// $_multiple_db->switchCIDatabaseToActiveTargetDB();
			//init session from target db
			$this->session->reinit();

			$message=null;
			$password=$this->player_model->getPasswordById($loggedPlayerId);
			$result['success']=$this->authentication->login_from_mdb($loggedUsername, $password, $message);
			if(!$result['success']){
				$result['message']=$message;
			}
		}else{
			$result['message']=lang('not available currency');
		}

		if($jsonp){
			$this->returnJsonpResult($result);
		}else{
			$this->returnJsonResult($result);
		}
	}

	/**
	 * Loads template for view based on regions in
	 * config > template.php
	 * Cloned from Iframe_module::loadTemplate().
     */
	private function loadTemplate($title, $description, $keywords, $activenav) {

		$this->template->write('title', $title);
		$this->template->write('description', $description);
		$this->template->write('keywords', $keywords);
		$this->template->write('activenav', $activenav);

	}

	private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
        return $sign;
    }

	private function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || empty($value)){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$api_keys = $this->config->item('api_key_player_center');
		foreach($api_keys as $key => $value ){
			$api_key = $key;
			break;
		}

		$signStr .= 'key='. $this->utils->encodePasswordMD5($api_key);
		return $signStr;
	}

}