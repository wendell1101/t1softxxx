<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

// require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/authController.php';

/**
 * General behaviors include :
 * * Setting message for user
 * * Set current language
 * * Player login
 * * Load template
 * * change currency
 *
 * @property Language_function $language_function
 * @property Authentication $authentication
 * @property Permissions $permissions
 * @property Lib_cloudflare_captcha $lib_cloudflare_captcha
 * @property CI_Form_validation $form_validation
 *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Auth extends AuthController {
	function __construct() {
		parent::__construct();

		$this->load->helper('url');
		$this->load->library(array('permissions', 'form_validation', 'utils'));

		$this->permissions->setPermissions();
	}

	public function index() {
		// Non-MDB mode, works for live_stable_prod
		if (!$this->utils->isEnabledMDB()) {
        	redirect('auth/login');
        	return;
        }

        // Code for MDB mode following
		$var_timeout_resume = $this->utils->getConfig('get_var_resuming_from_token_timeout');
		$timeout_resume = $this->session->userdata($var_timeout_resume);
		if ($this->authentication->isLoggedIn()) {
			if($this->utils->isSuperModeOnMDB()){
				//super report
                $redirect_to = 'super_report_management';
                if($this->session->userdata('redirect_to')) {
                    $redirect_to = $this->session->userdata('redirect_to');
                    $this->session->unset_userdata('redirect_to');
                }
                // OGP-21111: adding a marker when resuming from a timeout (for mdb mode)
				if (!empty($timeout_resume)) {
					$redirect_to = $this->utils->appendGetVarToRelativeUrl($redirect_to, $var_timeout_resume, 1);
					$this->session->unset_userdata($var_timeout_resume);
				}
				$this->utils->debug_log(__METHOD__, [ 'timeout_resume' => $timeout_resume, 'redirect_to'=> $redirect_to ]);
                redirect($redirect_to);
			}else{
				//regular home
                $redirect_to = 'home';
                if($this->session->userdata('redirect_to')) {
                    $redirect_to = $this->session->userdata('redirect_to');
                    $this->session->unset_userdata('redirect_to');
                }
                // OGP-21111: adding a marker when resuming from a timeout (for mdb mode)
				if (!empty($timeout_resume)) {
					$redirect_to = $this->utils->appendGetVarToRelativeUrl($redirect_to, $var_timeout_resume, 1);
					$this->session->unset_userdata($var_timeout_resume);
				}
				$this->utils->debug_log(__METHOD__, [ 'timeout_resume' => $timeout_resume, 'redirect_to'=> $redirect_to ]);
                redirect($redirect_to);
			}
		}else{
        	redirect('auth/login');
		}
	}

	/**
	 * set language
	 *
	 * @return	rendered Template with array of data
	 */
	public function setCurrentLanguage($language) {
		$this->language_function->setCurrentLanguage($language);
        $lang=Language_function::ISO2_LANG[$language];
		//set lang cookies
        $this->load->library(['session']);
        $this->session->setLanguageCookie($lang);
        redirect('auth/login');
	}

	/**
	 * Login user on the site
	 *
	 * @return void
	 */
	public function login($session_timeout = '0') {
		$this->load->model(['static_site', 'users']);
		$this->load->library(['ip_manager', 'user_functions']);
		$lang_int = $this->language_function->getCurrentLanguage();
		$lang     = $this->language_function->getLanguage($lang_int);
		$langISO2 = Language_function::ISO2_LANG[$lang_int];
		//set lang cookies
        $this->load->library(['session', 'captcha/lib_cloudflare_captcha']);
        $this->session->setLanguageCookie($langISO2);

        $sbe_contact_info = $this->utils->getConfig('sbe_contact_info');
		$data['company_title']   = $this->static_site->getDefaultCompanyTitle($lang);
		$data['company_title']   = (isset($sbe_contact_info['company_title'])) ? $sbe_contact_info['company_title'] : $this->static_site->getDefaultCompanyTitle($lang);
		$data['contact_website'] = (isset($sbe_contact_info['website'])) ? $sbe_contact_info['website'] : NULL;
		$data['contact_skype']   = (isset($sbe_contact_info['skype'])) ? $sbe_contact_info['skype'] : NULL;
		$data['contact_email']   = (isset($sbe_contact_info['email'])) ? $sbe_contact_info['email'] : NULL;
		$data['logo_icon'] = $this->utils->getConfig('default_sbe_login_logo');
		$data['availableCurrencyList']  =$this->utils->getAvailableCurrencyList();
		$data['activeCurrencyKeyOnMDB'] =$this->utils->getActiveCurrencyKeyOnMDB();
		$data['enableCFCaptcha']=$this->lib_cloudflare_captcha->isEnable();
		$data['cfCaptchaKey']=$this->lib_cloudflare_captcha->getClientKey();

        if($this->input->get('redirect')) {
            $this->session->set_userdata('redirect_to', urldecode($this->input->get('redirect')));
        }
        // OGP-21111: store a marker to session when resuming from a timeout
        $var_timeout_resume = $this->utils->getConfig('get_var_resuming_from_token_timeout');
        if ($this->input->get($var_timeout_resume)) {
        	$this->session->set_userdata($var_timeout_resume, 1);
        }

		$data['disabled'] = '';
		if ($this->authentication->isLoggedIn()) {
			if ($this->users->checkIfUserIsLocked($this->authentication->getUserId())) {
				$data['errors']['password'] = lang('con.a01');

				$this->authentication->logout();

				$this->load->view('login', $data);
			} else {
                $redirect_to = 'home';
                if($this->session->userdata('redirect_to')) {
                    $redirect_to = $this->session->userdata('redirect_to');
                    $this->session->unset_userdata('redirect_to');
                }
                // OGP-21111: append a GET var to mark timeout if marker exists in session
                $var_timeout_resume = $this->utils->getConfig('get_var_resuming_from_token_timeout');
                $timeout_resume = $this->session->userdata($var_timeout_resume);
				if (!empty($timeout_resume)) {
					$redirect_to = $this->utils->appendGetVarToRelativeUrl($redirect_to, $var_timeout_resume, 1);
					$this->session->unset_userdata($var_timeout_resume);
				}
				$this->utils->debug_log(__METHOD__, [ 'timeout_resume' => $timeout_resume, 'redirect_to'=> $redirect_to ]);
                redirect($redirect_to);
			}
		} else {
			$isUserIpAllowed = true;
			$this->load->model(['operatorglobalsettings', 'ip']);

			if ($this->operatorglobalsettings->getSettingValue('ip_rules') == 'true') {
				$ipAllowed=$this->ip->checkIfIpAllowedForAdmin();
				$this->utils->debug_log('checkIfIpAllowedForAdmin', ['ipAllowed'=>$ipAllowed,
					'getIpListFromXForwardedFor'=>$this->input->getIpListFromXForwardedFor(),
					'getRemoteAddr'=>$this->input->getRemoteAddr(),
					'getXRealipRemoteAddr'=>$this->input->getXRealipRemoteAddr(),
				]);
				if (!$ipAllowed) {
					//if ip is not allowed
					$data['errors']['password'] = lang('con.a02');
					$data['disabled'] = 'disabled';
					$isUserIpAllowed = false;
				}
			}

			$isValidCaptcha=true;
			$isPost=false;
			if (isset($_SERVER['REQUEST_METHOD'])) {
				$method=strtolower(@$_SERVER['REQUEST_METHOD']);
				$isPost=$method=='post';
			}
			// only for post
			if($isPost && $this->utils->getConfig('enable_cloudflare_captcha_on_login')){
				$token=$this->input->post('cf-turnstile-response');
				$ip=$this->utils->getIP();
				$success=$this->lib_cloudflare_captcha->validate($token, $ip);
				if(!$success){
					$data['errors']['password'] = lang('error.captcha');
					// $data['disabled'] = 'disabled';
					$isValidCaptcha = false;
				}
			}

			if ($isUserIpAllowed && $isValidCaptcha) {
				$this->form_validation->set_rules('login', 'Login', 'trim|required|xss_clean');
				$this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');
				$this->form_validation->set_rules('remember', 'Remember me', 'integer');

				$username=$this->input->post('login');
				$enable_otp_on_adminusers=$this->utils->isEnabledFeature('enable_otp_on_adminusers');
				$isEnabledOTPByUsername=$this->users->isEnabledOTPByUsername($username);
				if($enable_otp_on_adminusers && $isEnabledOTPByUsername){
					$this->form_validation->set_rules('otp_code', lang('2FA Code'), 'trim|required|xss_clean|callback_validate_otp_code');
				}
				$otpCode=$this->input->post('otp_code');

				$loginResult=Users::LOGIN_RESULT_FAILED;
				$data['errors'] = array();
				if ($this->form_validation->run()) {
					$this->utils->debug_log('form validated', $username, $otpCode,
						'enable_otp_on_adminusers', $enable_otp_on_adminusers, 'isEnabledOTPByUsername', $isEnabledOTPByUsername);
					$checker  = $this->users->selectUserDeleted($username);
					$userId = $this->users->getIdByUsername($username);
					$isLocked = $this->users->checkIfUserIsLocked($userId);

					$this->utils->debug_log('form validated', $username, $otpCode,'enable_otp_on_adminusers', $enable_otp_on_adminusers, 'isEnabledOTPByUsername', $isEnabledOTPByUsername, 'checker', $checker, 'isLocked', $isLocked);

					if($checker || $isLocked){
						$data['errors']['password'] = lang('con.a01');
						$data['disabled'] = '';
						$loginResult= $checker ? Users::LOGIN_RESULT_DELETED_USER : Users::LOGIN_RESULT_FAILED;
						$this->users->recordAdminLogin($username, $otpCode, $loginResult);
					}else{
						if (AuthController::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'adminuserLogin')) {
							$data['errors']['login'] = lang('No permission request too more');
							$data['disabled'] = 'disabled';

							$this->users->recordAdminLogin($username, $otpCode, $loginResult);
							$this->utils->debug_log('login user check acl', $username, $otpCode, $loginResult);
						}else{
							$password = $this->input->post('password');
							if ($this->authentication->login($username, $password)) {
								$this->users->setPasswordPlainForCurrentUser($password);
								$loginResult=Users::LOGIN_RESULT_SUCCESS;
								$this->users->recordAdminLogin($username, $otpCode, $loginResult);
								return redirect('/');
							} else {
								$errors = $this->authentication->get_error_message(); // fail
								foreach ($errors as $k => $v) {$data['errors'][$k] = $v;} //$data['errors'][$k] = $this->lang->line($v);
								$loginResult=Users::LOGIN_RESULT_FAILED;
								$this->users->recordAdminLogin($username, $otpCode, $loginResult);
							}
						}
					}
					$this->users->checkAndLockUserIfNeeded($username);
				}else{
					$this->utils->debug_log('form validation failed', $username, $otpCode,
						'enable_otp_on_adminusers', $enable_otp_on_adminusers, 'isEnabledOTPByUsername', $isEnabledOTPByUsername);
				}

				$data['disabled'] = '';
			}

			if ($session_timeout == '1') {
				$show_message = array(
					'result' => 'warning',
					'message' => lang('session.timeout'),
				);
				$this->session->set_userdata($show_message);
			}

			$this->load->view('login', $data);
		}
	}

	public function validate_otp_code($val) {
		$this->utils->debug_log('validate_otp_code', $val);

		$this->load->model('users');
		$success = true;

		$login=$this->input->post('login');
		$code=$val;

		$rlt= $this->users->validateOTPCodeByUsername($login, $code);
		$this->utils->debug_log('validate otp code', $login, $code);
		$success=$rlt['success'];
		if(!$success){
			$this->form_validation->set_message('validate_otp_code', lang('Wrong 2FA Code'));
		}
		return $success;
	}

	public function testConfig() {
		$this->ip_manager->checkIfIpAllowed();
		//$this->ip_manager->createConfigXML();
	}

	/**
	 * Logout user
	 *
	 * @return void
	 */
	public function logout() {
		$this->authentication->logout();
		redirect('/');
	}

	public function login_by_token($token){
		$success=false;
		$this->load->library(['authentication']);
		$this->load->model(array('common_token', 'users'));
		$adminId = $this->common_token->getAdminUserIdByToken($token);
		if(!empty($adminId)){
			$username=$this->users->getUsernameById($adminId);
			if(!empty($username)){
				$rlt=$this->authentication->set_login_user($username);
			}
		}

		$this->utils->debug_log('login by token', $token, $username, $adminId);

		return redirect('/');
	}

	public function change_active_currency(){
		//make sure we set session
		$this->load->library(['session']);

		$currencyKey=$this->input->get(Multiple_db::__OG_TARGET_DB);
		$result=['success'=>false];
		//validate currency
		if($currencyKey==Multiple_db::SUPER_TARGET_DB || $this->utils->isAvailableCurrencyKey($currencyKey)){
			$_multiple_db=Multiple_db::getSingletonInstance();
			$_multiple_db->init($currencyKey);
			$_multiple_db->rememberActiveTargetDB();

			$result['success']=true;
		}else{
			$result['message']=lang('not available currency');
		}

		$this->returnJsonResult($result);
	}

	/**
	 * SSO over MDB
	 * @param  string $currencyKey
	 *
	 */
	public function change_active_currency_for_logged($currencyKey) {
		$result=['success'=>false];
		$this->load->library(['authentication']);
		//still old db
		$loggedUserId=$this->authentication->getUserId();
		$loggedUsername=$this->authentication->getUsername();

		$this->utils->debug_log('loggedUserId', $loggedUserId, 'loggedUsername', $loggedUsername, 'ci db', $this->db->getOgTargetDB());
		if(empty($loggedUserId)){
			$result['message']=lang('session timeout, please relogin');
			return $this->returnJsonResult($result);
		}

		//validate currency
		if($currencyKey==Multiple_db::SUPER_TARGET_DB || $this->utils->isAvailableCurrencyKey($currencyKey)){
			$_multiple_db=Multiple_db::getSingletonInstance();
			$_multiple_db->switchCIDatabase($currencyKey);

			$message=null;
			$result['success']=$this->authentication->login_from_mdb($loggedUserId, $loggedUsername, $message);
			if(!$result['success']){
				$result['message']=$message;
			}else{
				$uri=$this->getUriFromReferer();
				$list=$this->utils->getConfig('switch_multiple_currency_white_list');
				if(!$this->matchUriInList($uri, $list)){
					$result['redirect_url']='/';
				}
			}
		}else{
			$result['message']=lang('not available currency');
		}

		return $this->returnJsonResult($result);
	}

	public function test_ip_allow(){
		$this->load->model(['ip']);
		$ipAllowed=$this->ip->checkIfIpAllowedForAdmin();

		$result=[
			'ipAllowed'=>$ipAllowed,
			'getIpListFromXForwardedFor'=>$this->input->getIpListFromXForwardedFor(),
			'getRemoteAddr'=>$this->input->getRemoteAddr(),
			'getXRealipRemoteAddr'=>$this->input->getXRealipRemoteAddr(),
			// '$_SERVER'=>$_SERVER,
		];
		$this->utils->debug_log('checkIfIpAllowedForAdmin', $result);

		$this->returnJsonResult($result);
	}
}

/* End of file auth.php */
/* Location: ./application/controllers/auth.php */
