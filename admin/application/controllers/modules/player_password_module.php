<?php

/**
 *
 * Class player_password_module
 *
 * General behaviors include :
 *
 * * Resetting a password
 * * Forgot a password
 * * Account recovery
 * * Check if username exist
 * * Webet Reset password
 * *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
trait player_password_module {

	/**
	 * overview : iframe_changePassword
	 *
	 * @return rendered template
	 */
	public function iframe_changePassword() {
		$data['currentLang'] = $this->language_function->getCurrentLanguage();
		$data['min_password_length'] = $this->utils->getConfig('default_min_size_password'); //self::MIN_PASSWORD_LENGTH;
		$data['max_password_length'] = $this->utils->getConfig('default_max_size_password');  //self::MAX_PASSWORD_LENGTH;
		$this->loadTemplate(lang('mod.changepass'), '', '', 'wallet');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/player/change_password', $data);
		$this->template->render();
	}

	/**
	 * overview : view cancel promo
	 *
	 * @return void
	 */
	public function postResetPassword() {

		$this->load->library(['player_library','utils']);
		$this->load->model(array('external_system', 'game_provider_auth', 'player_model'));

		$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
		$min_password_length = !empty($password_min_max_enabled) ? $password_min_max_enabled['min'] : $this->utils->getConfig('default_min_size_password');
		$max_password_length = !empty($password_min_max_enabled) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');
		$regex_password = $this->utils->getPasswordReg();

		$this->form_validation->set_rules('opassword', 'Current Password', 'trim|required|xss_clean');
		$this->form_validation->set_rules('password', lang('New Password'), 'trim|xss_clean|required|min_length['.$min_password_length.']|max_length['.$max_password_length.']|regex_match['.$regex_password.']');
		$this->form_validation->set_rules('cpassword', lang('Confirm New Password'), 'trim|xss_clean|required|matches[password]');

		if ($this->form_validation->run() == false) {
			//$message = "Please input correct details when resetting your password";
			$message = lang('notify.25');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}else{

				$this->alertMessage(2, $message);
				$this->iframe_changePassword();
			}
		} else {
			$opassword = $this->input->post('opassword');
			$password = $this->input->post('password');
			$player_id = $this->authentication->getPlayerId();

			$this->utils->debug_log(__METHOD__, [ 'player_id' => $player_id ]);

			$check = $this->player_library->isValidPassword($player_id, $opassword);
			$checkOldIsRepeatWithNew = $this->player_library->isValidPassword($player_id, $password);

			if (!$check) {
				// if password is incorrect
				// $message = "Your Password is incorrect. New Password cannot be save";
				$message = lang('notify.26');
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}else{
					$this->alertMessage(2, $message);
					$this->iframe_changePassword();
				}
			} else if ($checkOldIsRepeatWithNew) {
				// if new password is equate old password
				// $message = "The new password can not be duplicated with the old password, Please re-enter your new password";
				$message = lang('notify.105');
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}else{
					$this->alertMessage(2, $message);
					$this->iframe_changePassword();
				}
			} else {

				// save change password  in player_password_history
				$this->player_model->insertPasswordHistory($player_id, Player_model::CHANGE_PASSWORD, $this->utils->encodePassword($password));

				$player = $this->player_model->getPlayerById($player_id);

				$data = array('password' => $password);
				$this->player_functions->resetPassword($player_id, $data);

				$gameApis = $this->external_system->getAllGameApis();
				foreach ($gameApis as $key) {
					$api = $this->utils->loadExternalSystemLibObject($key['id']);
					$oldPassword = $this->game_provider_auth->getPasswordByPlayerId($player_id, $key['id']);
					$api->changePassword($player->username, $oldPassword, $password);
				}

				//save changes to playerupdatehistory
				$this->savePlayerUpdateLog($player_id, lang('system.word8'), $this->authentication->getUsername());

				//$message = "Your Password has successfully changed";
				$message = lang('notify.27');

				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'success', 'msg' => $message));
					return;
				}else{
					$this->alertMessage(1, $message);
					$this->iframe_changePassword();
				}
			}
		}
	}

	public function postResetMainPassword() {

		$this->load->library(array('utils','player_library'));
		$this->load->model(array('external_system', 'game_provider_auth', 'player_model'));

		$sess_player_can_directly_set_passwd = $this->session->userdata('player_can_directly_set_passwd');

		$directlyChangePassword = $this->utils->checkPlayerCanDirectlyChangePassword() || $sess_player_can_directly_set_passwd;

		$this->utils->debug_log(__METHOD__, [ 'checkPlayerCanDirectlyChangePassword' => $this->utils->checkPlayerCanDirectlyChangePassword(), 'sess_player_can_directly_set_passwd' => $sess_player_can_directly_set_passwd, 'directlyChangePassword' => $directlyChangePassword ]);

		if(!$directlyChangePassword) {
			$this->form_validation->set_rules('opassword', 'Current Password', 'trim|required|xss_clean');
		}

		$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
		$min_password_length = !empty($password_min_max_enabled) ? $password_min_max_enabled['min'] : $this->utils->getConfig('default_min_size_password');
		$max_password_length = !empty($password_min_max_enabled) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');
		$regex_password = $this->utils->getPasswordReg();

		$this->form_validation->set_rules('password', lang('New Password'), 'trim|xss_clean|required|min_length['.$min_password_length.']|max_length['.$max_password_length.']|regex_match['.$regex_password.']');
		$this->form_validation->set_rules('cpassword', lang('Confirm New Password'), 'trim|xss_clean|required|matches[password]');

		if ($this->form_validation->run() == false) {
			$this->utils->debug_log(__METHOD__, 'validation_errors', validation_errors());
			//$message = "Please input correct details when resetting your password";
			$message = lang('notify.25');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}else{

				$this->alertMessage(2, $message);
			}
		} else {
			$opassword = $this->input->post('opassword');
			$password = $this->input->post('password');
			$player_id = $this->authentication->getPlayerId();

			$this->utils->debug_log(__METHOD__, 'playr',$player_id);
			$check = $this->player_library->isValidPassword($player_id, $opassword);

			$this->utils->debug_log(__METHOD__, 'check password', $check);

			$password_holder = $this->session->userdata('password_holder');
			$this->utils->debug_log(__METHOD__, 'unset_userdata password_holder', $password_holder, '_COOKIE', $_COOKIE);
			if (!empty($_COOKIE['remember_me'])) {
				unset($_COOKIE['remember_me']);
				setcookie('remember_me', null, -1, '/');
			}

			if(!empty($password_holder)){
				$this->session->unset_userdata('password_holder');
			}

			if($directlyChangePassword) $check = true;
			if (!$check) {
				// if password is incorrect
				//$message = "Your Password is incorrect. New Password cannot be save";
				$message = lang('notify.26');
				if ($this->input->is_ajax_request()) {

					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}else{
					$this->alertMessage(2, $message);
				}

			} else {
				if ($this->utils->getConfig('restrict_player_login_pwd_cannot_same_withdrawal_pwd')) {
					$player = $this->player_model->getPlayerArrayById($player_id);
					$withdrawal_pwd = $player['withdraw_password'];
					$this->utils->debug_log(__METHOD__, 'withdrawal_pwd', $withdrawal_pwd);
					if ($withdrawal_pwd == $password) {
						$message = lang('notify.restrict_player_login_pwd_cannot_same_withdrawal_pwd');
						if ($this->input->is_ajax_request()) {

							$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
							return;
						}else{
							$this->alertMessage(2, $message);
						}
					}
				}

				// save change password  in player_password_history
				$this->player_model->insertPasswordHistory($player_id, Player_model::CHANGE_PASSWORD, $this->utils->encodePassword($password));

				$player = $this->player_model->getPlayerById($player_id);
				$data = array(
					'password' => $password,
					'is_phone_registered' => PHONE_REGISTERED_YET_AND_CHANGE_PASSWORD
				);
				$this->player_functions->resetPassword($player_id, $data);

				//sync
				$username=$this->player_model->getUsernameById($player_id);
				$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

				//save changes to playerupdatehistory
				$this->savePlayerUpdateLog($player_id, lang('system.word8'), $this->authentication->getUsername());

				//$message = "Your Password has successfully changed";
				$message = lang('notify.27');

				$this->session->unset_userdata('player_can_directly_set_passwd');

				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'success', 'msg' => $message));
					return;

				}else{
					$this->alertMessage(1, $message);
				}
			}
		}
	}

	public function gameResetPassword() {

		$this->load->library('utils');
		$this->load->model(array('external_system', 'game_provider_auth', 'player_model'));

		$player_id = $this->authentication->getPlayerId();
		$player = $this->player_model->getPlayerById($player_id);
		$game_return="";

		$password = $this->input->post('password');
		$api_id = $this->input->post('api_id');

		$api = $this->utils->loadExternalSystemLibObject($api_id);
		$oldPassword = $this->game_provider_auth->getPasswordByPlayerId($player_id, $api_id);

		if(!$this->game_provider_auth->isRegisterd($player_id, $api_id)){
			$this->returnJsonResult(array('status' => 'success', 'msg' =>lang('pay.modify') .' '. lang('Success') ));
		}else{

			$game_return = $api->changePassword($player->username, $oldPassword, $password);

			if ($game_return['success']){
				if (isset($game_return['unimplemented']) && $game_return['unimplemented']) {
					$this->returnJsonResult(array('status' => 'unimplemented', 'msg' =>lang('cannot_change_password_because_unimplemented') ));
				} else {
					$this->returnJsonResult(array('status' => 'success', 'msg' =>lang('pay.modify') .' '. lang('Success') ));
				}
			} else{
				$this->returnJsonResult(array('status' => 'error', 'msg' =>lang('pay.modify') .' '.  lang('Failed') ));
			}
		}
	}

	/**
	 * overview : change update withdrawal password
	 *
	 * @return rendered template
	 */
	public function iframe_changeWithdrawPassword() {
		if( $this->utils->getConfig('withdraw_verification') == 'withdrawal_password') {
			$this->load->model(['player_model']);

			$data['currentLang'] = $this->language_function->getCurrentLanguage();
			$data['player'] = $this->player_model->getPlayerInfoDetailById($this->authentication->getPlayerId());

			$this->loadTemplate(lang('Withdraw Password Setting'), '', '', 'wallet');
			$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/player/change_withdraw_password', $data);
			$this->template->render();
		} else {
			redirect(site_url().'player_center/dashboard');
		}
	}

	/**
	 * overview : post reset withdrawal password
	 *
	 * @return void
	 */
	public function postResetWithdrawPassword() {
		$this->load->model(['player_model']);

		$player = $this->player_model->getPlayerInfoDetailById($this->authentication->getPlayerId());

		if(!empty($player['withdraw_password'])) {
			$this->form_validation->set_rules('current_password', 'Withdraw Current Password', 'trim|required|xss_clean');
		}
		$this->form_validation->set_rules('new_password',  'Withdraw New Password', 'trim|required|min_length[4]|max_length[12]|xss_clean|alpha_numeric');
		$this->form_validation->set_rules('confirm_new_password', 'Withdraw Confirm Password', 'trim|required|min_length[4]|max_length[12]|xss_clean|alpha_numeric|matches[new_password]');

		if ($this->form_validation->run() == false) {
			$message = lang('notify.111');

			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}

			$this->alertMessage(2, $message);
			$this->iframe_changeWithdrawPassword();
		} else {
			$current_password = $this->input->post('current_password');
			$new_password = $this->input->post('new_password');
			$player_id = $this->authentication->getPlayerId();

			$check=$this->player_model->validateWithdrawalPassword($player_id, $current_password);

			if (!$check && !empty($player['withdraw_password']) ) {
				$message = lang('Withdraw Incorrect Password');

				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}

				$this->alertMessage(2, $message);
				$this->iframe_changeWithdrawPassword();
			} else {
				$data['withdraw_password'] = $new_password;

				if ($this->utils->getConfig('restrict_player_withdrawal_pwd_cannot_same_login_pwd')) {
					$this->load->library(array('player_library'));
					$check_pwd = $this->player_library->checkLoginPwdCannotSameWithdrawalPwd($player_id, $new_password);
					$this->utils->debug_log(__METHOD__, 'withdrawal_pwd', $new_password, $check_pwd);
					if (!$check_pwd) {
						$message = lang('notify.restrict_player_withdrawal_pwd_cannot_same_login_pwd');
						if ($this->input->is_ajax_request()) {

							$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
							return;
						}else{
							$this->alertMessage(2, $message);
						}
					}
				}

				$this->player_model->resetPassword($player_id, $data);

				#sending email
				$this->load->library(['email_manager']);
		        $template = $this->email_manager->template('player', 'player_change_withdrawal_password_successfully', array('player_id' => $player_id, 'new_withdrawal_password' => $new_password));
		        $template_enabled = $template->getIsEnableByTemplateName(true);
		        if($template_enabled['enable']){
		        	$template->sendingEmail($player['email'], Queue_result::CALLER_TYPE_PLAYER, $player_id);
		        }

				//save changes to playerupdatehistory
				$this->savePlayerUpdateLog($player_id, lang('Withdraw Reset Password'), $this->authentication->getUsername());

				//sync
				$username = $this->player_model->getUsernameById($player_id);
				$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

				$message = lang('Withdraw Successfully Change');

				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'success', 'msg' => $message));
					return;
				}

				$this->alertMessage(1, $message);
				$this->iframe_changeWithdrawPassword();
			}
		}
	}

	/**
	 * overview : post reset withdrawal password by sms
	 *
	 * @return void
	 */
	public function postResetWithdrawPasswordBySms() {
		$this->load->model(['player_model','sms_verification']);
		$this->load->library(array('session'));
		$player = $this->player_model->getPlayerInfoDetailById($this->authentication->getPlayerId());
		$player_id = $this->authentication->getPlayerId();
		$session_id = $this->session->userdata('session_id');
		$contact_number = $player['contactNumber'];
		$usage = sms_verification::USAGE_SMSAPI_SECURITY;

		$this->form_validation->set_rules('reset_code', lang('Verification Code'), 'trim|xss_clean|required');

		$this->form_validation->set_rules('new_w_password',  'Withdraw New Password', 'trim|required|min_length[4]|max_length[12]|xss_clean|alpha_numeric');
		$this->form_validation->set_rules('cfnew_password', 'Withdraw Confirm Password', 'trim|required|min_length[4]|max_length[12]|xss_clean|alpha_numeric|matches[new_w_password]');

		if ($this->form_validation->run() == false) {
			$message = lang('notify.111');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
		} else {
			$reset_code = $this->input->post('reset_code');
			$new_password = $this->input->post('new_w_password');
			$this->utils->debug_log('========== post params', $this->input->post());

			$success = $this->sms_verification->validateVerificationCode($player_id, $session_id, $contact_number, $reset_code, $usage);
			$this->utils->debug_log('========== sms_verification_code result =====', $success);
			if(!$success) {
				$message=lang('Verify SMS Code Failed');
				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
					return;
				}
			}else {
				$data['withdraw_password'] = $new_password;
				$this->player_model->resetPassword($player_id, $data);

				#sending email
				$this->load->library(['email_manager']);
		        $template = $this->email_manager->template('player', 'player_change_withdrawal_password_successfully', array('player_id' => $player_id, 'new_withdrawal_password' => $new_password));
		        $template_enabled = $template->getIsEnableByTemplateName(true);
		        if($template_enabled['enable']){
					$template->sendingEmail($player['email'], Queue_result::CALLER_TYPE_PLAYER, $player_id);
		        }

				//save changes to playerupdatehistory
				$this->savePlayerUpdateLog($player_id, lang('Withdraw Reset Password'), $this->authentication->getUsername());

				//sync
				$username = $this->player_model->getUsernameById($player_id);
				$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

				$message = lang('Withdraw Successfully Change');

				if ($this->input->is_ajax_request()) {
					$this->returnJsonResult(array('status' => 'success', 'msg' => $message));
					return;
				}
			}
		}
	}

	# Provides a configurable page with 3 options of password recovery
	# options configured in Registration Settings
	public function forget_password_select() {
		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
			return;
		}

		$data['password_recovery_option_1'] = FALSE;
		$data['password_recovery_option_2'] = FALSE;
		$data['password_recovery_option_3'] = FALSE;

        $forgetPasswordEnabled = $this->utils->checkForgetPasswordEnabled();
        if($forgetPasswordEnabled){
            if(isset($forgetPasswordEnabled['recovery_options']['security_question'])){
                $data['password_recovery_option_1'] = $forgetPasswordEnabled['recovery_options']['security_question']['enabled'];
                $data['password_recovery_option_1_url'] = $forgetPasswordEnabled['recovery_options']['security_question']['url'];
            }
            if(isset($forgetPasswordEnabled['recovery_options']['sms'])){
                $data['password_recovery_option_2'] = $forgetPasswordEnabled['recovery_options']['sms']['enabled'];
                $data['password_recovery_option_2_url'] = $forgetPasswordEnabled['recovery_options']['sms']['url'];
            }
            if(isset($forgetPasswordEnabled['recovery_options']['email'])){
                $data['password_recovery_option_3'] = $forgetPasswordEnabled['recovery_options']['email']['enabled'];
                $data['password_recovery_option_3_url'] = $forgetPasswordEnabled['recovery_options']['email']['url'];
            }
        }

        //only has one recovery options
        if(!!$data['password_recovery_option_1'] && !$data['password_recovery_option_2'] && !$data['password_recovery_option_3']){
            return redirect($data['password_recovery_option_1_url']);
        }else if(!$data['password_recovery_option_1'] && !!$data['password_recovery_option_2'] && !$data['password_recovery_option_3']){
            return redirect($data['password_recovery_option_2_url']);
        }else if(!$data['password_recovery_option_1'] && !$data['password_recovery_option_2'] && !!$data['password_recovery_option_3']){
            return redirect($data['password_recovery_option_3_url']);
        }

        $this->loadTemplate(lang('Forgot Password'), '', '', 'player');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/forget_password_select', $data);
		$this->template->render();
	}

	/**
	 * overview : forget password process
	 * Recovery option 1, uses security question.
	 *
	 * @param int $player_id
	 */
	public function forgot_password($player_id = null) {
		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
		}

		$data['currentLang'] = $this->language_function->getCurrentLanguage();
		if ($player_id == null) {
			$this->form_validation->set_rules('username', 'Username', 'trim|xss_clean|required|callback_usernameExist');
			if ($this->form_validation->run() == false) {
				$this->loadTemplate('Forgot Password', '', '', 'player');
				$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/forgot_password_1', $data);
				$this->template->render();
			} else {

				$this->load->model('registration_setting');
				$username = $this->input->post('username');
				$player = (array) $this->player->getPlayerByLogin($username);

				if($this->registration_setting->isRegistrationFieldVisible('Security Question')) {
					$secretQuestion = $player['secretQuestion'];
  					$secretAnswer = $player['secretAnswer'];
  					if (!empty($secretQuestion) && !empty($secretAnswer)) {
						$data['player'] = $player;
						$this->loadTemplate('Forgot Password', '', '', 'player');
						$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/forgot_password_2', $data);
						$this->template->render();
					} else {
						$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('lang.forgotpasswdMsgNoSecQues'));
						redirect('iframe/auth/login', 'refresh');
					}
				} else {
					$this->password_reset_process($player);
				}
			}
		} else {
			$player = (array) $this->player->getPlayerById($player_id);

			$this->form_validation->set_rules('secretAnswer', 'Secret Answer', 'trim|xss_clean|required|callback_checkSecretAnswer[' . $player_id . ']');
			if ($this->form_validation->run() == false) {
				$data['player'] = $player;
				$this->loadTemplate('Forgot Password', '', '', 'player');
				$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/forgot_password_2', $data);
				$this->template->render();
			} else {
				$this->password_reset_process($player);
			}
		}
	}

	private function password_reset_process($player){
		$this->load->model(array('queue_result','player_model','game_provider_auth'));

		$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
		$maxpassword = !empty($password_min_max_enabled) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');
		$newPassword = substr(str_shuffle(MD5(microtime())), 0, $maxpassword);
		$data = array(
			'password' => $this->utils->encodePassword($newPassword),
		);

		// save player password history
		$this->player_model->insertPasswordHistory($player['playerId'], Player_model::RESET_PASSWORD, $this->utils->encodePassword($newPassword));
		$this->player->resetPassword($player['playerId'], $data); // reset player password

		$isSyncApi = $this->utils->isEnabledFeature('sync_api_password_on_update');
		if($isSyncApi){
			$gameApis = $this->utils->getAllCurrentGameSystemList();
			foreach ($gameApis as $apiId) {
				$api = $this->utils->loadExternalSystemLibObject($apiId);
				$oldPassword = $this->game_provider_auth->getPasswordByPlayerId($player['playerId'], $apiId);
				$api->changePassword($player['username'], $oldPassword, $newPassword);
			}
		}

		#sending email
		$this->load->library(['email_manager']);
        $template = $this->email_manager->template('player', 'player_change_login_password_successfully', array('player_id' => $player['playerId'], 'new_login_password' => $newPassword));
        $template_enabled = $template->getIsEnableByTemplateName(true);
        $result['success'] = true;
        if ($template_enabled['enable']) {
        	$template->sendingEmail($player['email'], Queue_result::CALLER_TYPE_PLAYER, $player['playerId']);
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('lang.pls.check.your.email.for.password'));
        }
        else {
        	$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('Security') . ' - ' . lang('Security Question') . '<br>' . $template_enabled['message']);
        }

        redirect('iframe/auth/login', 'refresh');
	}

	public function password_recovery_sms($source = 'sms') {
		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
			return;
		}
		$data['source'] = $source;
		$this->loadTemplate(lang('Find password by SMS'), '', '', 'player');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/password_recovery_sms',$data);
		$this->template->render();
	}

	public function password_recovery_email() {
		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
			return;
		}

		$this->loadTemplate(lang('Find password by email'), '', '', 'player');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/password_recovery_email');
		$this->template->render();
	}

	public function password_recovery_reset_code() {
		# This prevents the password reset page showing after login
		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
			return;
		}
		$this->load->library(array('session'));
		$this->load->model(array('sms_verification'));
		$session_id = $this->session->userdata('session_id');
		$data['username'] = $this->input->post('username');
		$data['title']    = $this->input->post('title');
		$data['source']   = $this->input->post('source');
		$this->utils->debug_log("===========source: ", $data['source']);
		$player           = (array) $this->player->getPlayerByLogin($data['username']);
		$referer = !empty($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'])['path'] : '';
		$playerContact = $this->player->getPlayerContactInfo($player['playerId']);
		$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
		$min_password_length = $data['min_password_length'] = !empty($password_min_max_enabled) ? $password_min_max_enabled['min'] : $this->utils->getConfig('default_min_size_password');
		$max_password_length = $data['max_password_length'] = !empty($password_min_max_enabled) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');
		$regex_password = $data['regex_password'] = $this->utils->getPasswordReg();

		if($referer != '/iframe_module/password_recovery_reset_code') { # Display form
			$this->loadTemplate($data['title'], '', '', 'player');
			$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/password_recovery_reset_code', $data);
			$this->template->render();
		} else {

			$this->form_validation->set_rules('reset_code', lang('Verification Code'), 'trim|xss_clean|required');
			$this->form_validation->set_rules('password', lang('New Password'), 'trim|xss_clean|required|min_length['.$min_password_length.']|max_length['.$max_password_length.']|regex_match['.$regex_password.']');
			$this->form_validation->set_rules('confirm_password', lang('Confirm New Password'), 'trim|xss_clean|required|matches[password]');

			if ($this->form_validation->run() == false) {
				$this->loadTemplate($data['title'], '', '', 'player');
				$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/password_recovery_reset_code', $data);
				$this->template->render();
			} else {
				$reset_code = $this->input->post('reset_code');
				if(!empty($data['source'])){
					if($data['source'] == 'sms'){

						$use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
						$usage = !empty($use_new_sms_api_setting) ? sms_verification::USAGE_SMSAPI_FORGOTPASSWORD : sms_verification::USAGE_PASSWORD_RECOVERY;

						if ($this->sms_verification->validateVerificationCode($player['playerId'], $session_id, $playerContact['contactNumber'], $reset_code, $usage)) {
						# Process password change
						$this->load->library(array('salt'));
						$encrypted_password = $this->salt->encrypt($this->input->post('password'), $this->getDeskeyOG());
						$this->player->editPlayer(array(
							'password' => $encrypted_password,
							'resetCode' => null,
							'resetExpire' => null,
						), $player['playerId']);

						//save changes to playerupdatehistory
						$this->savePlayerUpdateLog($player['playerId'], lang('system.word8'), $data['username']);

						$this->alertMessage(1, lang('forgot.12'));
						redirect('iframe/auth/login');
						}
					}
				}else{
					if ($this->checkResetCodeResetExpire($reset_code, $player['playerId'])) {
						# Process password change
						$this->load->library(array('salt'));
						$encrypted_password = $this->salt->encrypt($this->input->post('password'), $this->getDeskeyOG());
						$this->player->editPlayer(array(
							'password' => $encrypted_password,
							'resetCode' => null,
							'resetExpire' => null,
						), $player['playerId']);

						//save changes to playerupdatehistory
						$this->savePlayerUpdateLog($player['playerId'], lang('system.word8'), $data['username']);

						$this->alertMessage(1, lang('forgot.12'));
						redirect('iframe/auth/login');
					}
				}

			$this->loadTemplate($data['title'], '', '', 'player');
			$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/password_recovery_reset_code', $data);
			$this->template->render();

			}
		}
	}

	# Generates a random reset code used for reseting forgotten password
	# if $short is true, this reset code is for manual entry. Random 6 digits.
	public function generateResetCode($player_id, $short = false) {
		$reset_code = strtolower($this->utils->generateRandomCode(5, uniqid($player_id), time()));
		if($short) {
			$reset_code = mt_rand(100000, 999999);
		}

		$expire_mins = $this->CI->config->item('password_reset_code_expire_mins');
		$this->player->editPlayer(array(
			'resetCode' => $reset_code,
			'resetExpire' => date('Y-m-d H:i:s', strtotime('+'.$expire_mins.' mins')),
		), $player_id);
		return $reset_code;
	}

	/**
	 * overview : account recovery
	 *
	 * @param int		$player_id
	 * @param string	$reset_code
	 */
	public function account_recovery($player_id = null, $reset_code = null) {

		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
		}

		if($player_id == null && $reset_code == null) {
			$reset_code = $this->input->post('reset_code');
			$username = $this->input->post('username');
			$player = (array) $this->player->getPlayerByLogin($username);
			$player_id = $player['playerId'];
		}

		if ($this->checkResetCode($reset_code, $player_id)) {
			$this->form_validation->set_rules('password', 'Password', 'trim|xss_clean|required');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|xss_clean|required|matches[password]');
			if ($this->form_validation->run() == false) {
				$data['username'] = $this->player->getPlayerById($player_id)['username'];
				$data['player_id'] = $player_id;
				$data['reset_code'] = $reset_code;
				$this->loadTemplate('Forgot Password', '', '', 'player');
				$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/change_password', $data);
				$this->template->render();
			} else {
				$this->load->library(array('salt'));
				$encrypted_password = $this->salt->encrypt($this->input->post('password'), $this->getDeskeyOG());
				$this->player->editPlayer(array(
					'password' => $encrypted_password,
					'resetCode' => null,
					'resetExpire' => null,
				), $player_id);
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('forgot.12'));
				redirect('iframe/auth/login');
			}
		} else {
			$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('forgot.14'));
			redirect('iframe_module/forgot_password');
		}
	}

	# To be called via ajax. If phone number matches, an SMS with password reset code will be sent
	# Returns json data, for example
	# { "success" : true }
	# { "success" : false, "message" : "Phone number does not match" }
	public function find_password_sms($username, $phone_number, $captcha) {
		$result = array('success' => false);
		$this->load->library(array('session', 'sms/sms_sender' ,'voice/voice_sender'));
		$this->load->model(array('sms_verification', 'player_model'));

		if ($this->utils->getConfig('disabled_sms')) {
			return $this->returnJsonResult(array('success' => false, 'message' => lang('Disabled SMS')));
		}

		if(!$this->checkCaptcha($captcha)) {
			$result['message'] = lang('Wrong captcha');
			$this->returnJsonResult($result);
			return;
		}

		$player = (array) $this->player->getPlayerByLogin($username);
		$playerContact = $this->player->getPlayerContactInfo($player['playerId']);
		$dialingCode = $playerContact['dialing_code'];

		# Does phone number match the record?
		if(strcasecmp(trim($phone_number), trim($playerContact['contactNumber'])) !== 0) {
			$result['message'] = lang('Phone number does not match');
			$this->utils->error_log("Find password for [$username] by sms [$phone_number] failed: ", $result);
			$this->returnJsonResult($result);
			return;
		}

		$sessionId = $this->session->userdata('session_id');
		$lastSmsTime = $this->session->userdata('last_sms_time');
		$smsCooldownTime = $this->config->item('sms_cooldown_time');

		if(empty($lastSmsTime)){
			//load from redis
			$lastSmsTime=$this->utils->readRedis($playerContact['contactNumber'].'_last_sms_time');
		}

		# Should not send SMS without valid session ID
		if(!$sessionId) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('Unknown error')));
			return;
		}

		# Check the send count with ip or mobile on cooldown period
		if ($this->sms_verification->checkIPAndMobileLastTIme($smsCooldownTime, $playerContact['contactNumber'])) {
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

		$numCount = $this->sms_verification->getTodaySMSCountFor($playerContact['contactNumber']);
		if($numCount >= $this->config->item('sms_max_per_num_per_day')) {
			$this->utils->error_log("Sent maximum [$numCount] SMS to this number today.");
			$this->returnJsonResult(array('success' => false, 'message' => sprintf(lang('One username is only allowed to send %s texts per day, please try again tomorrow.'), $this->config->item('sms_max_per_num_per_day')), 'isDisplay' => true));
			return;
		}

        $use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
		$usage = !empty($use_new_sms_api_setting) ? sms_verification::USAGE_SMSAPI_FORGOTPASSWORD : sms_verification::USAGE_PASSWORD_RECOVERY;

		# Obtain the reset code
		// $resetCode = $this->generateResetCode($player['playerId'], true);
		# Send SMS with reset code
		$mobileNum = !empty($dialingCode)? $dialingCode.'|'.$playerContact['contactNumber'] : $playerContact['contactNumber'];
		$resetCode = $this->sms_verification->getVerificationCode($player['playerId'], $sessionId, $playerContact['contactNumber'], $usage);
		$this->utils->debug_log("Reset code: ", $resetCode);

		$useSmsApi = null;
        if ($use_new_sms_api_setting) {
			#restrictArea = action type
			list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($player['playerId'], $phone_number, $usage, $sessionId);
			$this->utils->debug_log(__METHOD__, 'use new sms api',$useSmsApi, $sms_setting_msg, $usage);

			if (empty($useSmsApi)) {
				$this->returnJsonResult(array('success' => false, 'message' => $sms_setting_msg));
				return;
			}
		}

		if( !empty($this->config->item('switch_voice_under_sms_limit_num_per_day'))){
			if( $numCount >= $this->config->item('switch_voice_under_sms_limit_num_per_day')){
				$useVoiceApi = $this->voice_sender->getvoiceApiName();
				if($useVoiceApi != 'disable'){
					if ($this->voice_sender->send($mobileNum, $resetCode, $useVoiceApi)) {
						$this->session->set_userdata('last_sms_time', time());
						$this->utils->writeRedis($mobileNum.'_last_sms_time', time());
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

			$content = sprintf(lang('Your verification code is: %s'), $resetCode);
			$callerType = Queue_result::CALLER_TYPE_PLAYER;
			$caller = $player['playerId'];
			$state = null;

			$this->lib_queue->addRemoteSMSJob($mobileNum, $content, $callerType, $caller, $state);

		} else {

			$msg = sprintf(lang('Your verification code is: %s'), $resetCode);
			$sendSuccess = $this->sms_sender->send($mobileNum, $msg, $useSmsApi);
			if(!$sendSuccess) {
				$result['message'] = $this->sms_sender->getLastError();
				$this->utils->error_log("Find password for [$username] by sms [$phone_number] failed: ", $result);
				$this->returnJsonResult($result);
				return;
			}
		}

		$result['success'] = true;
		$this->utils->debug_log("Find password for [$username] by sms [$phone_number] succeed");
		$this->returnJsonResult($result);
		return;
	}

	# To be called via ajax. If phone number matches, an SMS with password reset code will be sent
	# Returns json data, for example
	# { "success" : true }
	# { "success" : false, "message" : "Phone number does not match" }
	public function find_password_voice($username, $phone_number, $captcha) {
		$result = array('success' => false);
		$this->load->library(array('session', 'sms/sms_sender' ,'voice/voice_sender'));
		$this->load->model(array('sms_verification', 'player_model'));

		if ($this->utils->getConfig('disabled_sms')) {
			return $this->returnJsonResult(array('success' => false, 'message' => lang('Disabled SMS')));
		}

		if(!$this->checkCaptcha($captcha)) {
			$result['message'] = lang('Wrong captcha');
			$this->returnJsonResult($result);
			return;
		}

		$player = (array) $this->player->getPlayerByLogin($username);
		$playerContact = $this->player->getPlayerContactInfo($player['playerId']);
		$dialingCode = $playerContact['dialing_code'];

		# Does phone number match the record?
		if(strcasecmp(trim($phone_number), trim($playerContact['contactNumber'])) !== 0) {
			$result['message'] = lang('Phone number does not match');
			$this->utils->error_log("Find password for [$username] by sms [$phone_number] failed: ", $result);
			$this->returnJsonResult($result);
			return;
		}

		$sessionId = $this->session->userdata('session_id');
		$lastSmsTime = $this->session->userdata('last_sms_time');
		$smsCooldownTime = $this->config->item('sms_cooldown_time');

		if(empty($lastSmsTime)){
			//load from redis
			$lastSmsTime=$this->utils->readRedis($playerContact['contactNumber'].'_last_sms_time');
		}

		# Should not send SMS without valid session ID
		if(!$sessionId) {
			$this->returnJsonResult(array('success' => false, 'message' => lang('Unknown error')));
			return;
		}

		# Check the send count with ip or mobile on cooldown period
		if ($this->sms_verification->checkIPAndMobileLastTIme($smsCooldownTime, $playerContact['contactNumber'])) {
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
			$this->utils->error_log("Sent [$codeCount] SMS in the past minute, exceeded config max [".$this->config->item('sms_global_max_per_minute')."]");
			$this->returnJsonResult(array('success' => false, 'message' => lang('Voice service process is currently busy. Please wait.')));
			return;
		}

		$numCount = $this->sms_verification->getTodaySMSCountFor($playerContact['contactNumber']);
		if($numCount >= $this->config->item('sms_max_per_num_per_day')) {
			$this->utils->error_log("Sent maximum [$numCount] SMS to this number today.");
			$this->returnJsonResult(array('success' => false, 'message' => sprintf(lang('One username is only allowed to send %s texts per day, please try again tomorrow.'), $this->config->item('sms_max_per_num_per_day')), 'isDisplay' => true));
			return;
		}

		# Obtain the reset code
		// $resetCode = $this->generateResetCode($player['playerId'], true);
		# Send SMS with reset code
		$mobileNum = !empty($dialingCode)? $dialingCode.'|'.$playerContact['contactNumber'] : $playerContact['contactNumber'];
		$resetCode = $this->sms_verification->getVerificationCode($player['playerId'], $sessionId, $playerContact['contactNumber'], sms_verification::USAGE_PASSWORD_RECOVERY);
		$this->utils->debug_log("Reset code: ", $resetCode);

		$useVoiceApi = $this->voice_sender->getvoiceApiName();

		if ($this->voice_sender->send($mobileNum, $resetCode, $useVoiceApi)) {
			$this->session->set_userdata('last_sms_time', time());
			$this->utils->writeRedis($mobileNum.'_last_sms_time', time());
			$this->returnJsonResult(array('success' => true));
		} else {
			$this->returnJsonResult(array('success' => false, 'message' => $this->voice_sender->getLastError()));
		}
		return;
	}

	# To be called via ajax. If phone number matches, an email with new password will be sent
	# Returns json data, for example
	# { "success" : true }
	# { "success" : false, "message" : "Email address does not match" }
	public function find_password_email($username = null, $email = null, $captcha = null) {
		if (static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'default')) {
			return $this->_show_last_check_acl_response('json');
		}

		$result = array('success' => false);
		if($this->input->is_ajax_request()) {
			$username = $this->input->post('username');
            $email = $this->input->post('email');
			$captcha = $this->input->post('captcha');
			if(empty($username) || empty($email) || empty($captcha)) {

				$result['message'] = lang("player.mp14");
				$this->returnJsonResult($result);
				return;
			}
		}
		if(!$this->checkCaptcha($captcha)) {
			$result['message'] = lang('error.captcha');

			if(!empty($this->utils->getConfig('captcha_text_align'))){
				$captcha_text_align = $this->utils->getConfig('captcha_text_align');
				$result['captcha_text_align'] = $captcha_text_align;
			}

			$this->returnJsonResult($result);
			return;
		}

		$email = urldecode($email);
		$player = (array) $this->player->getPlayerByLogin($username);
		if(empty($player)) {
			$result['message'] = lang('Player not found.');
            $this->utils->error_log("Find password for [$username] by email [$email] failed: ", $result);
            $this->returnJsonResult($result);
            return;
		}
		# Does email address match the record?
		if(strcasecmp(trim($email), trim($player['email'])) !== 0) {
			$result['message'] = lang('Email does not match');
			$this->utils->error_log("Find password for [$username] by email [$email] failed: ", $result);
			$this->returnJsonResult($result);
			return;
		}

        # Email verification
       if($player['verified_email'] != Player_model::EMAIL_IS_VERIFIED) {
        $result['message'] = lang('Email is not verified!');
        $this->utils->error_log("Verify email for [$username]: ", $result);
        $this->returnJsonResult($result);
        return;
        }

		if(!$this->isCooldown()) {
			$result['message'] = lang('Sending request too frequent.');
			$this->returnJsonResult($result);
			return;
		}

		# Obtain the reset code
		$resetCode = $this->generateResetCode($player['playerId'], true);
		$this->utils->debug_log("Reset code: ", $resetCode);

		#sending email
		$this->load->library(['email_manager']);
		$this->load->model(array('email_verification'));
		$template_name = 'player_forgot_login_password';
        $template = $this->email_manager->template('player', $template_name, array('player_id' => $player['playerId'], 'verify_code' => $resetCode));
        $template_enabled = $template->getIsEnableByTemplateName(true);
        $result['success'] = true;

        if ($template_enabled['enable']) {
        	$email = $this->player->getPlayerById($player['playerId'])['email'];
        	$job_token = $template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $player['playerId']);
			$record_id = $this->email_verification->recordReport($player['playerId'], $email, $template_name, $resetCode, $job_token);
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('code_successfully_sent_thru_email'));
        } else {
			$record_id = $this->email_verification->recordReport($player['playerId'], $email, $template_name, $resetCode, null, email_verification::SENDING_STATUS_FAILED);
        	$this->alertMessage(self::MESSAGE_TYPE_WARNING, lang('email_template_name_player_forgot_login_password') .'<br>'. $template_enabled['message']);
        }

		$this->returnJsonResult($result);
		return;
	}

	# Checks whether captcha matches current session's stored value
	private function checkCaptcha($captcha) {

		$namespace = $this->input->post('captcha_namespace');
		switch($this->utils->getCapchaSetting($namespace)) {
			case 'hcaptcha':
				$config['call_socks5_proxy'] = $this->config->item('call_socks5_proxy');
				$config['timeout_second']    = $this->utils->getConfig('enabled_captcha_of_3rdparty')['hcaptcha_timeout_seconds'];
				$config['connect_timeout']   = $this->utils->getConfig('enabled_captcha_of_3rdparty')['hcaptcha_timeout_seconds'];
				$config['is_post']           = TRUE;
				$params['secret'] = $this->utils->getConfig('enabled_captcha_of_3rdparty')['secret'];
				$params['response'] = $captcha;
				$response_result = $this->utils->httpCall('https://hcaptcha.com/siteverify', $params, $config);
				$json_result = json_decode($response_result[1],true);

				if($json_result['success']){
					return true;
				}else{
					return false;
				}
				break;
			default:
				$this->load->library('captcha/securimage');
				$securimage = new Securimage();
				return $securimage->check($captcha);
		}
	}

	private function isCooldown() {
		$cooldown = $this->utils->getConfig('password_recovery_cooldown') ?: 0;
		$lastCallTime = $this->session->userdata('password_recovery_timestamp');
		$currentTime = time();
		if($currentTime - $lastCallTime <= $cooldown) {
			$this->utils->error_log("Last call time [$lastCallTime], Current time [$currentTime], Cooldown of [$cooldown] sec not reached.");
			return false;
		}
		$this->session->set_userdata('password_recovery_timestamp', $currentTime);
		return true;
	}

	/**
	 * overview : check if username exist
	 *
	 * @param  string	$username
	 * @return bool
	 */
	public function usernameExist($username) {
		$this->load->model(['player_model']);
		$result = $this->player_model->getPlayer(array(
			'username' => $username,
		));
		if ($result) {
			return true;
		} else {
			$this->form_validation->set_message('usernameExist', $this->utils->renderLang(lang('forgot.15'), site_url('/player_center/iframe_register')));
			return false;
		}
	}

	/**
	 * overview: check if user email exist
	 *
	 * @return bool
	 */
	public function checkUserEmail() {
		$this->load->model(['player_model']);
        $username = $this->input->post('username');
        $email = $this->input->post('email');

		$result = $this->player_model->isUserEmailExist($username,$email);

		if ($result) {
			return true;
		} else {
			$this->form_validation->set_message('checkUserEmail', lang('reg.a18'));
			return false;
		}
	}

	/**
	 * overview : check secret answer
	 *
	 * @param string	$secret_answer
	 * @param int		$player_id
	 * @return bool
	 */
	public function checkSecretAnswer($secret_answer, $player_id) {
		$this->load->model(['player_model']);
		$result = $this->player_model->getPlayer(array(
			'playerId' => $player_id,
			'secretAnswer' => $secret_answer,
		));
		if ($result) {
			return true;
		} else {
			$this->form_validation->set_message('checkSecretAnswer', lang('forgot.11'));
			return false;
		}
	}

	/**
	 * overview : check reset code
	 *
	 * @param string	$reset_code
	 * @param int		$player_id
	 * @return bool
	 */
	public function checkResetCode($reset_code, $player_id) {
		$this->load->model(['player_model']);
		$result = $this->player_model->getPlayer(array(
			'playerId' => $player_id,
			'resetCode' => $reset_code,
			'resetExpire >' => $this->utils->getNowForMysql(),
		));
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * overview : check reset code reset expire
	 *
	 * @param string	$reset_code
	 * @param int		$player_id
	 * @return bool
	 */
	public function checkResetCodeResetExpire($reset_code, $player_id) {
		$this->load->model(['player_model']);
		$array = $this->CI->player_model->getResetCodeExpireByPlayerId($player_id);
		$resetCode = $array[0]['resetCode'];
		$resetExpire = $array[0]['resetExpire'];

		$this->utils->debug_log(__METHOD__, 'resetCode', $resetCode, 'resetExpire', $resetExpire, 'now', $this->utils->getNowForMysql());

		if(!empty($resetExpire) && $resetExpire < date("Y-m-d H:i:s'")){
			$this->alertMessage(2, lang('forgot.14'));
			redirect('iframe_module/password_recovery_email');
			return;
		}else {
			if($resetCode != $reset_code){
				$this->alertMessage(2, lang('verification.code.incorrect'));
				return false;
			}else {
				return true;
			}
		}
	}

	/**
	 * overview : forgot password process
	 *
	 * @param string $player_id
     *
     * @deprecated Marked by elvis
     * @see static::forget_password_select
	 */
	public function forgotPassword($player_id = null){

		if ($this->authentication->isLoggedIn()) {
			$this->goPlayerHome();
		}

		$data['currentLang'] = $this->language_function->getCurrentLanguage();

		$this->loadTemplate('Forgot Password', '', '', 'player');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate().'/auth/forgot_password_1', $data);
		$this->template->render();

	}

	/**
	 * overview : ajax post reset user password
	 *
	 * @return void
	 */
	public function ajaxPostResetUserPassword($password = null) {
		// save change password  in player_password_history
		if($password){
			$this->load->model(array('external_system', 'game_provider_auth', 'player_model'));
			$player_id = $this->authentication->getPlayerId();
			$this->player_model->insertPasswordHistory($player_id, Player_model::CHANGE_PASSWORD, $this->utils->encodePassword($password));

			$player = $this->player_model->getPlayerById($player_id);

			$data = array('password' => $password);
			$this->player_functions->resetPassword($player_id, $data);

			$gameApis = $this->external_system->getAllGameApis();
			foreach ($gameApis as $key) {
				$api = $this->utils->loadExternalSystemLibObject($key['id']);
				$oldPassword = $this->game_provider_auth->getPasswordByPlayerId($player_id, $key['id']);
				$api->changePassword($player->username, $oldPassword, $password);
			}

			//save changes to playerupdatehistory
			$this->savePlayerUpdateLog($player_id, lang('system.word8'), $this->authentication->getUsername());

			//$message = "Your Password has successfully changed";
			$message = lang('notify.27');

			// if ($this->input->is_ajax_request()) {

			// 	$this->returnJsonResult(array('status' => 'success', 'msg' => $message));
			// 	return;

			// }else{
			return $this->alertMessage(1, $message);

				// $this->iframe_changePassword();
			// }
		}
	}

	/**
	 * Check User Password if valid
	 * @param  string $withdrawal_password
	 * @return bolean/array
	 */
	public function checkUserPassword($password){
		$player_id = $this->authentication->getPlayerId();
		$check = $this->player_library->isValidPassword($player_id, $password);

		if (!$check) {
			// if password is incorrect
			$message = lang('notify.26');
			if ($this->input->is_ajax_request()) {
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
		}
	}

	/**
	 * Withdraw password reset method, REALLY accepts old/new withdraw passwords by POST, unlike ajaxPostResetWithdrawPassword() above
	 * OGP-3061
	 *
	 * @uses	POST['current_password']	The old password
	 * @uses	POST['new_password']		The new password
	 * @return	JSON	['status', 'msg']
	 */
	public function ajaxRealPostResetWithdrawPassword() {
		$current_password = $this->input->post('current_password');
		$new_password = $this->input->post('new_password');

		$ret = [
			'status'	=> 'error' ,
			'message' 	=> 'exec_incomplete'
		];

		try {
			// Get playerId
			$player_id = $this->authentication->getPlayerId();
			if (empty($player_id)) {
				throw new Exception('Login failed, try it later');
			}

			$player=$this->player_model->getPlayerArrayById($player_id);

			// Basic input checks
			if(!empty($player['withdraw_password'])) {
				if (empty($current_password)) {
					throw new Exception('mod.oldPassNotEmpty');
				}
			}
			if (empty($new_password)) { throw new Exception('mod.newNewPassNotEmpty'); }

			// Verify old password
			if(!empty($player['withdraw_password'])) {
				$check = $this->player_model->validateWithdrawalPassword($player_id, $current_password);
				$this->utils->debug_log('ajaxRealPostResetWithdrawPassword', 'old_passwd', $current_password, 'check_result', $check);
				if (!$check) {
					throw new Exception('mod.oldPassNotMatch');
				}
			}

			// Set new password
			$this->player_functions->resetWithrawalPassword($player_id, $new_password);
			$this->savePlayerUpdateLog($player_id, lang('Withdraw Reset Password'), $this->authentication->getUsername());
			$message = lang('Withdraw Successfully Change');
			$this->alertMessage(1, $message);

			$ret['status']	= 'success';
			$ret['message'] = $message;
		}
		catch (Exception $ex) {
			$ret['status']	= 'error';
			$ret['message'] = lang($ex->getMessage());
		}
		finally {
			$this->returnJsonResult($ret);
		}

	}

	/**
	 * Check Withdrawal Password if valid
	 * @param  string $withdrawal_password
	 * @return bolean/array
	 */
	public function checkWithdrawalPassword($withdrawal_password = null){
		$player_id = $this->authentication->getPlayerId();

		$checkWithdrawPassword=$this->player_model->validateWithdrawalPassword($player_id, $withdrawal_password);

		if( ! $checkWithdrawPassword ){
			$message = lang('Invalid Withdrawal Password');
			if( $this->input->is_ajax_request() ){
				$this->utils->debug_log($message);
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
		}else{
			return true;
		}
	}

	/**
	 * overview : webet post reset password
	 */
	public function webetPostResetPassword(){
		$data['currentLang'] = $this->language_function->getCurrentLanguage();

		$username = $this->input->post('username');
		$this->form_validation->set_rules('username', 'Username', 'trim|xss_clean|required|callback_usernameExist');


		if ($this->form_validation->run() == false) {
			$this->forgotPassword();
		} else {

			$this->form_validation->set_rules('email', 'Email', 'trim|xss_clean|required|callback_checkUserEmail');

			if ($this->form_validation->run() == false) {
				$this->forgotPassword();
			} else {

				$player = (array) $this->player->getPlayerByLogin($username);

				$player_id = $player['playerId'];
				$email = $player['email'];
				$reset_code = strtolower($this->utils->generateRandomCode(5, uniqid($player_id), time()));

				$url = "/player_center/account_recovery/$player_id/$reset_code";

				$this->load->model(array('queue_result'));
				$this->utils->sendMail($email, null, null, 'Forgot Password', 'Click the link to reset your password:'.site_url($url), Queue_result::CALLER_TYPE_PLAYER, $player_id);

				$this->player->editPlayer(array(
					'resetCode' => $reset_code,
					'resetExpire' => date('Y-m-d H:i:s', strtotime('+1 hour')),
				), $player_id);

				$this->alertMessage(1, lang('lang.pls.check.your.email'));
				redirect('iframe/auth/login', 'refresh');
			}
		}
	}
	#END FOR WEBET ONLY #################################################################################################################################################

	public function forgotWithdrawalPassworSendEmail() {
		$player_id = $this->authentication->getPlayerId();
		$player = $this->player->getPlayerById($player_id);
		$email = $this->input->post('email');

		if($player["email"] != $email) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Email address entered does not match email on your profile'));
			$results = ['success'=> false, 'message'=>lang('Email address entered does not match email on your profile')];
		}else{
			$maxpassword = $this->utils->getConfig('default_max_size_password');
			$new_password = substr(str_shuffle(MD5(microtime())), 0, $maxpassword);
			$this->player_functions->resetWithrawalPassword($player_id, $new_password);

			#sending email
			$this->load->library(['email_manager']);
	        $template = $this->email_manager->template('player', 'player_change_withdrawal_password_successfully', array('player_id' => $player_id, 'new_withdrawal_password' => $new_password));
	        $template_enabled = $template->getIsEnableByTemplateName(true);
	        if($template_enabled['enable']){
	        	$template->sendingEmail($player['email'], Queue_result::CALLER_TYPE_PLAYER, $player_id);
	        	$message = lang('lang.pls.check.your.email');
				$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
				$results = ['success'=> true, 'message'=> $message];
	        } else {
	        	$message = lang('Forgot Withdrawal Password').'<br>'.$template_enabled['message'];
				$this->alertMessage(self::MESSAGE_TYPE_WARNING, $message);
				$results = ['success'=> false, 'message'=> $message];
	        }
		}

		$this->returnJsonResult($results);
	}

	/**
	 * Generic reset password routine, shorthand for 4 tasks
	 * (1) insert password history (2) reset password (3) change game password (4) save update log
	 * DO NOT DIRECTLY CALL THIS METHOD WITHOUT VALIDATION
	 * OGP-5402
	 *
	 * @param	string	$password		new password
	 * @param 	string	$old_password	Old password
	 *
	 * @return	none
	 */
	public function __resetPasswordAfterValidation($player_id, $password, $extra_log_info = null, $is_api_call = false) {
		$this->load->library([ 'utils', 'player_functions' ]);
		$this->load->model(['external_system', 'game_provider_auth', 'player_model']);

		$ret = [ 'code' => 0, 'mesg' => null ];

		try {
			$this->player_model->insertPasswordHistory($player_id, Player_model::CHANGE_PASSWORD, $this->utils->encodePassword($password));

			$player = $this->player_model->getPlayerById($player_id);
			$data = array('password' => $password);
			$this->player_functions->resetPassword($player_id, $data);

			$api_cp_res = [];
			// $gameApis = $this->external_system->getAllGameApis();
			$gameApis = $this->external_system->getAllGameApisAndStatus();

			foreach ($gameApis as $key) {
				$this->utils->debug_log(__METHOD__, "Player '{$player_id}' - Changing game password", [ 'game' => $key]);
				if ($key['status'] != 1) {
					$this->utils->debug_log(__METHOD__, "Player '{$player_id}' - Game API disabled,  skipping", [ 'game' => $key]);
					continue;
				}
				// If player not registered in game, skip and report success
				if (!$this->game_provider_auth->isRegisterd($player_id, $key['id'])) {
					$this->utils->debug_log(__METHOD__, "Player '{$player_id}' - not registered in game,  skipping", [ 'game' => $key]);
					$api_cp_res[] = [
						'id'			=> $key['id'] ,
						'system_code'	=> $key['system_code'] ,
						'success'		=> true
					];
						continue;
				}
				// Or, go on with real password change flow
				$api = $this->utils->loadExternalSystemLibObject($key['id']);
				$oldPassword = $this->game_provider_auth->getPasswordByPlayerId($player_id, $key['id']);
				$cp_res = $api->changePassword($player->username, $oldPassword, $password);
				$this->utils->debug_log(__METHOD__, "Player '{$player_id}' - game password change result", [ 'game' => $key, 'result' => $cp_res['success'] ]);
				$api_cp_res[] = [
					'id'			=> $key['id'] ,
					'system_code'	=> $key['system_code'] ,
					'success'		=> $cp_res['success']
				];
			}

			// If not api call, include admin username in player update log; or, skip the username
			$update_username = $extra_log_info;
			if (empty($is_api_call)) {
				$update_username = "{$this->authentication->getUsername()} $update_username";
			}

			// Format mesg for player update log
			$log_mesg = lang('system.word8');
			if (!empty($extra_log_info))  {
				$log_mesg = $log_mesg . " $extra_log_info";
			}

			// Log the update
			$this->player_functions->savePlayerUpdateLog($player_id, $log_mesg, $update_username);

			$ret['mesg'] = $api_cp_res;

		} catch (Exception $ex) {
			$ret = [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ];
		} finally {
			return $ret;
		}
	}
}
////END OF FILE/////////