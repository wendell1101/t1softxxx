<?php

require_once dirname(__FILE__) . '/iframe_module.php';

class Ole_sso extends Iframe_module {

    private $uuid;
    private $ssoSetting;
    private $ole_sso_player_tableName = 'ole_sso_player';
    const RETURN_SUCCESS_CODE = 'success';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['player_library']);
        $this->ssoSetting = $this->utils->getConfig('ole_sso_setting');
    }

    public function index()
    {
        if(!$this->ssoSetting){
            $this->goPlayerLogin();
        }

        $params = $this->getInputGetAndPost();
        $this->checkError($params);
        $this->utils->debug_log("=======params", $params);

        $username          = $params['username'];
        $password          = $params['password'];

        $parts = parse_url($params['referer']);
        $query = $parts['query'];        
        parse_str($query, $params);        
        $url = $params['redirect_uri'];

        $uuid              = uniqid('ole_sso_');
        $ip                = $this->utils->getIP();
        $status            = Third_party_login::THIRD_PARTY_LOGIN_STATUS_REQUEST;
        $pre_register_form = [];
        
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

        // check if player exist
        $playerId = $this->player_model->getPlayerIdByUsername($username);
        $ole_player = $this->third_party_login->getPlayerByPlayerId($this->ole_sso_player_tableName, $playerId);
        $this->third_party_login->insertThirdPartyLogin($uuid, $ip, $status, json_encode($extra_info), json_encode($pre_register_form));
        $this->utils->debug_log("=======ole_player", $ole_player);

        $data = [
            'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_AUTH
        ];
        $this->third_party_login->updateThirdPartyLoginByUuid($uuid, $data);
        $playerlogin = $this->login($username, $password, $params);

        if($playerlogin['status']){
            $data = [
                'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_SUCCESS
            ];
            $this->third_party_login->updateThirdPartyLoginByUuid($uuid, $data);
            $playerId = $this->player_model->getPlayerIdByUsername($username);
            $this->utils->debug_log("=======player", $playerId);
            $access_token = $this->common_token->getPlayerToken($playerId);
            $data = [
                'player_id'       => $playerId,
                'username'        => $username,
                'access_token'    => $access_token,
                'expiration_date' => date('Y-m-d H:i:s', strtotime('+2 day')),
            ];
            if($ole_player){
                $this->third_party_login->updatePlayersByPlayerId($this->ole_sso_player_tableName, $playerId, $data);
            }else{
                $this->third_party_login->insertOlePlayers($this->ole_sso_player_tableName, $data);
            }
            // header("Location: $url?access_token=$access_token");
            // redirect($url."?access_token=$access_token");
            $postData = [
                'status' => 'sso',
                'url'    => $url."?access_token=".$access_token
            ];
            $this->returnJsonResult($postData);
        }else{
            $urlComponents = parse_url($params['referer']);
            $callback_url = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'];
            header("Location: $callback_url");
        }    
    }

    private function checkError($params)
    {
        if(empty($params)){
            $this->utils->error_log('Ole Sso Validation Failed');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Ole Sso Validation Failed'));
            redirect('/iframe/auth/login');

        } elseif(isset($params['error'])){
            if(!empty($this->uuid)){
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Ole Sso Validation Failed', $params);
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Ole Sso Validation Failed'));
            redirect('/iframe/auth/login');
        }
    }
    public function checkAccessToken()
    {
        $data['status'] = false;
        $params = $this->getInputGetAndPost();
        $jsonData = json_decode(file_get_contents('php://input'), true);

        if(empty($params) && empty($jsonData)){
            $data['message']= 'params is empty';
            $this->returnJsonResult($data);
            return;
        }

        $client_id = isset($params['client_id']) ? $params['client_id'] : $jsonData['client_id'];
        $secret_key = isset($params['secret_key']) ? $params['secret_key'] : $jsonData['secret_key'];
        $access_token = isset($params['access_token']) ? $params['access_token'] : $jsonData['access_token'];
        $date = date('Y-m-d H:i:s');

        if($client_id != $this->ssoSetting['client_id'] || $secret_key != $this->ssoSetting['secret_key'])
        {
            $data ['message'] = 'Ole Sso Validation Failed';
            $this->returnJsonResult($data);
            return;
        }

        $player = $this->third_party_login->getPlayerByAccessToken($this->ole_sso_player_tableName, $access_token, $date);
        if($player){
            $data['status'] = true;
        }else{
            $data['message'] = 'Invalid Access Token';
        }
        $this->returnJsonResult($data);
    }

    public function getUserInfo()
    {
        $data['status'] = false;
        $params = $this->getInputGetAndPost();
        $jsonData = json_decode(file_get_contents('php://input'), true);

        if(empty($params) && empty($jsonData)){
            $data['message']= 'params is empty';
            $this->returnJsonResult($data);
            return;
        }

        $client_id = isset($params['client_id']) ? $params['client_id'] : $jsonData['client_id'];
        $secret_key = isset($params['secret_key']) ? $params['secret_key'] : $jsonData['secret_key'];
        $access_token = isset($params['access_token']) ? $params['access_token'] : $jsonData['access_token'];
        $date = date('Y-m-d H:i:s');
        
        if($client_id != $this->ssoSetting['client_id'] || $secret_key != $this->ssoSetting['secret_key'])
        {
            $data ['message'] = 'Ole Sso Validation Failed';
            $this->returnJsonResult($data);
            return;
        }

        $player = $this->third_party_login->getPlayerByAccessToken($this->ole_sso_player_tableName, $access_token, $date);
        if($player){
            $data = [
                'status' => true,
                'username' => $player['username'],
                'player_id' => $player['player_id']
            ];
        }else{
            $data['message'] = 'Invalid Access Token';
        }
        $this->returnJsonResult($data);
    }

    private function login($username, $password, $params, $shouldBeNothing=null) {

		$this->utils->debug_log('get ip from player', $this->input->ip_address(), isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : 'no x-real-ip');

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
        //  list($city, $countryName) = $this->utils->getIpCityAndCountry($ip);
        //  $block_page_url = $this->country_rules->getBlockedPageUrl($countryName, $city);
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

				// if($gamePlatform){
	            // 	$success = $result['success'];
	            // 	# CHECK IF GAME API IS ACTIVE STATUS OR MAINTENANCE
			    //     if ($this->utils->setNotActiveOrMaintenance($gamePlatform)) {
			    //         $success = false;
			    //     }
			    //     if($success){
			    //     	if($gamePlatform == GGPOKER_GAME_API){
				//         	return $gameLoginResult =  $this->custom_ggpoker_login($gamePlatform,$redirectUri);
				// 		}
				// 		if($gamePlatform == ONEWORKS_API){
				//         	return $this->oneworks_app_auth($gamePlatform);
				//         }
			    //     }

	            //     $this->returnJsonResult(array('success' => $success));
	            //     return;
	            // }

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
				// return redirect($url);
                $returnData = [
                    'status' => true
                ];
                return $returnData;
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
        
        $callback_url = $params['referer'];
        $urlComponents = parse_url($callback_url);

  
        if (isset($urlComponents['query'])) {
            parse_str($urlComponents['query'], $queryParams);
            $this->utils->debug_log("=======queryParams", $queryParams);

            if (isset($queryParams['username_error'])) {
                unset($queryParams['username_error']);
            }
            if(isset($data['username_error'])){
                $queryParams['username_error'] = $data['username_error'];
            }

            if (isset($queryParams['password_error'])) {
                unset($queryParams['password_error']);
            }

            if(isset($data['password_error'])){
                $queryParams['password_error'] = $data['password_error'];
            }

            $newQuery = http_build_query($queryParams);

            $urlComponents['query'] = $newQuery;
        }

        $callback_url = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'];

        if (isset($urlComponents['query'])) {
            $callback_url .= '?' . $urlComponents['query'];
        }
        $this->utils->debug_log("=======callback_url", $callback_url);
        redirect($callback_url);
	}

    public function createHtmlForm($url, $params) {
        $formId = 'f_' . random_string('unique');
        $html = '<form name="' . $formId . '" id="' . $formId . '" method="POST" action="' . $url . '">';
        if (is_array($params)) {
            foreach ($params as $name => $val) {
                $html = $html . "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . htmlentities($val) . "\">\n";
            }
        }
        $html = $html . "<button type=\"hidden\" style=\"display:none\" id=\"submit_form_btn_" . $formId . "\">\n";
        $html = $html . '</form>';
        return array($html, $formId);
    }
}
