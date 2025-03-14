<?php

/**
 * Mobile registration API suite, version new-1
 * OGP-13106, bulit on ole777cn's request, reengineered from OGP-7822, OGP-7983
 * July 2019
 *
 * @see		OGP-7822
 * @see		t1t_comapi_module_mobile_reg1
 */
trait t1t_comapi_module_sms_registration {

	/**
	 * First step of SMS registration
	 * Sends validation code to given mobile number (contactNumber)
	 *
	 * @uses	POST:api_key		api key given by system
	 * @uses	POST:contactNumber	Player's mobile number
	 *
	 * @return	JSON	Standard JSON return structure
	 */
	public function smsRegSendSms() {
		$api_key = $this->input->post('api_key');

		if (!$this->__checkKey($api_key)) { return; }

		$res = null;

		try {
			$this->load->library([ 'comapi_lib' ]);
			$this->load->model([ 'player_model', 'sms_verification' ]);

			$contact_number = trim($this->input->post('contactNumber', 1));
			$dialing_code = trim($this->input->post('dialingCode', 1));
			$tuid = $this->comapi_lib->tuid();
			$usage_tuid = "comapi_smsreg_{$tuid}";
			$request = [ 'api_key' => $api_key, 'contactNumber' => $contact_number ];
			$this->comapi_log(__METHOD__, 'request', $request);

    		if ($this->utils->isSMSEnabled() == false && empty($this->utils->getConfig('demo_mode'))) {
				throw new Exception(lang('SMS service is disabled'), self::CODE_SREG_SMS_SERVICE_DISABLED);
			}

			if (!preg_match('/^\d+$/', $contact_number)) {
				throw new Exception(lang('contactNumber format invalid'), self::CODE_SREG_CONTACT_NUMBER_INVALID);
			}

			if ($this->player_model->checkContactExist($contact_number)) {
				throw new Exception(lang('contactNumber is already in use by another player'), self::CODE_SREG_CONTACT_NUMBER_IN_USE);
			}

			$restrict_area = null;
			if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
				$restrict_area = Sms_verification::USAGE_SMSAPI_REGISTER;
			}
			$this->comapi_log(__METHOD__, 'contact_number', $contact_number, 'restrict_area', $restrict_area);

			$send_res = $this->comapi_lib->comapi_send_sms(null, $contact_number, $usage_tuid, null, $dialing_code, 'also_use_redis', $restrict_area);

			// $this->utils->debug_log(__METHOD__, 'mobileCreatePlayer_send_sms return', $send_res);

			if ($send_res['code'] != 0) {
				$this->comapi_log(__METHOD__, 'Error in Comapi_lib::comapi_send_sms()', $send_res['mesg_debug']);
				throw new Exception(lang($send_res['mesg']), $send_res['code']);
			}

			$ret = [
			    'success'   => true,
			    'code'      => self::CODE_SUCCESS,
			    'mesg'      => 'Validation SMS sent successfully',
			    'result'    => [ 'tuid' => $tuid ]
			];

			$this->comapi_log(__METHOD__, 'response', $ret, $request);
		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $request);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}

	} // End function smsRegSendSms()

	/**
	 * Step 2 of SMS registration, working as guard of Api_common::createPlayer()
	 * Verify tuid/verify_code/contactNumber tuple, if successful, pass on to createPlayer()
	 *
	 * @uses	POST:api_key		api key given by system
	 * @uses	POST:contactNumber	Player's mobile number
	 * @uses	POST:tuid			tuid in successful return of smsRegSendSms
	 * @uses	POST:verify_code	Verification code in SMS received by player
	 * @uses	(other createPlayer POST arguments)
	 *       						Will be passed to createPlayer() if verification is successful
	 *
	 * @return	JSON
	 */
	public function smsRegCreatePlayer() {
		$api_key = $this->input->post('api_key');

		if (!$this->__checkKey($api_key)) { return; }

		$res = null;

		try {
			$this->load->library([ 'comapi_lib' ]);
			$this->load->model([ 'sms_verification' ]);

			$contact_number = trim($this->input->post('contactNumber', true));
			$tuid			= trim($this->input->post('tuid', true));
			$verify_code	= trim($this->input->post('verify_code', true));

			$request = [ 'api_key' => $api_key, 'contactNumber' => $contact_number, 'tuid' => $tuid, 'verify_code' => $verify_code ];
			$reg_fields = array_slice($this->input->post(), 0, 20, 'preserve_keys');
			$this->comapi_log(__METHOD__, 'request', $request, 'fields', $reg_fields);

			$usage_tuid = "comapi_smsreg_{$tuid}";

    		if ($this->utils->isSMSEnabled() == false && empty($this->utils->getConfig('demo_mode'))) {
				throw new Exception(lang('SMS service is disabled'), self::CODE_SREG_SMS_SERVICE_DISABLED);
			}

			if (!preg_match('/^\d+$/', $contact_number)) {
				throw new Exception(lang('contactNumber format invalid'), self::CODE_SREG_CONTACT_NUMBER_INVALID);
			}

			if (!$this->comapi_lib->tuid_match($tuid)) {
				throw new Exception(lang('tuid format invalid'), self::CODE_SREG_TUID_INVALID);
			}

			if (!preg_match('/^\d+$/', $verify_code)) {
				throw new Exception(lang('verify_code format invalid'), self::CODE_SREG_VERIFY_CODE_INVALID);
			}

			$code_redis_verify_res = $this->comapi_lib->_redisVerCodeVerify($usage_tuid, $contact_number, strval($verify_code));

			$this->utils->debug_log(__METHOD__, 'code_redis_verify_res', $code_redis_verify_res);

			// $usage_tuid = "comapi_smsreg_{$tuid}";

			$code_verify_res = $this->sms_verification->validateVerificationCode_2(null, Sms_verification::SESSION_ID_DEFAULT, $contact_number, $verify_code, $usage_tuid);

			$this->utils->debug_log(__METHOD__, 'code_verify_res', $code_verify_res);

			if ($code_redis_verify_res['success'] == false) {
				if (!$code_verify_res) {
					$fail_unit = 'both';
				}
				else {
					$fail_unit = 'comapi_lib::_redisVerCodeVerify()';
				}

				$this->comapi_log(__METHOD__, 'Code verify failed in',  $fail_unit);
				throw new Exception(lang("Sms code verification failed, check verify_code or contactNumber"), self::CODE_SREG_CODE_VERIFY_FAILED);
			}

			$this->comapi_log(__METHOD__, 'contactNumber', $contact_number, 'contactNumber verified successfully, going on to Api_common::createPlayer()');

            $is_registr_captcha_enabled = $this->utils->getConfig('enabled_captcha_on_player_center_api_smsRegCreatePlayer') &&
                $this->operatorglobalsettings->getSettingJson('registration_captcha_enabled');
            if( ! empty($is_registr_captcha_enabled) ){
                $register_res = [];
                $ret = [];
                $this->load->library('captcha/securimage');
                $captcha_code	= $this->input->post('captcha_code');
                $captcha_token	= $this->input->post('captcha_token');
                $jsonArray = null;
                if( ! empty($captcha_token) ){
                    $captcha_cache_token = sprintf('%s-%s', Securimage::cache_prefix, $captcha_token);
                    $jsonArray = $this->utils->getJsonFromCache($captcha_cache_token);
                    // delete cache, only use once
                    $this->utils->deleteCache($captcha_cache_token);
                }
                $this->utils->debug_log('181.process captcha_code', $captcha_code, 'captcha_token', $captcha_token, 'jsonArray', $jsonArray);
                if( empty($captcha_code)
                    || empty($captcha_token)
                    || empty($jsonArray)
                ){
                    $register_res['mesg'] = lang('Captcha invalid or empty');
                    $register_res['code'] = self::CODE_SREG_CAPTCHA_MISSING;
                    $ret = $register_res;
                    $ret['success'] = false;
                    throw new Exception($register_res['mesg'], $register_res['code']);
                }else if(  strtolower($jsonArray['code']) != strtolower($captcha_code) ){
                    $register_res['mesg'] = lang('Captcha code invalid');
                    $register_res['code'] = self::CODE_SREG_INVALID_CAPTCHA_CODE;
                    $ret = $register_res;
                    $ret['success'] = false;
                    throw new Exception($register_res['mesg'], $register_res['code']);
                }
                $this->createPlayer_option['CAPTCHA_VALIDATED_METHOD'] = __FUNCTION__; // createPlayer() will ignore Captcha check
            } // EOF if( ! empty($is_registr_captcha_enabled) ){...

			// Transfer to Api_common::createPlayer()
			// OGP-14852: convey post-registration option to createPlayer()
			$this->createPlayer_option['AFTER_REG_VALIDATE_PHONE'] = 1;

			// OGP-21751 workaround: suppress default reg sms code check, as the code is already checked and deactivated (not available for check again) above
			// return $this->createPlayer('suppress_default_reg_sms_code_check');
			return $this->createPlayer();

		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $request);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];

			$this->returnApiResponseByArray($ret);
		}

	} // End function smsRegCreatePlayer()

} // End of trait t1t_comapi_module_sms_registration