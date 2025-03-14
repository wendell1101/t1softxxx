<?php

/**
 * player account function
 *
 * uri: /player, /otp, /login/otp, /captcha-image, /verification-questions
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property Player_friend_referral $player_friend_referral
 */
trait player_account_module{

	public function player($action, $additional=null, $append=null){
		if(!$this->initApi()){
			return;
		}

		$this->utils->debug_log('enter player', $action, $additional, $append);
		$this->load->library(['playerapi_lib', 'payment_library', 'player_library']);
		$this->load->model(['comapi_reports', 'playerapi_model', 'payment_account', 'playerbankdetails', 'communication_preference_model', 'player_model']);
		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'register':
				if($request_method == 'POST'){
					return $this->playerAccountRegister($additional);
				}
				break;
			case 'profile':
				if($request_method == 'GET') {
					return $this->playerAccountGetProfile($this->player_id);
				}
				else if($request_method == 'POST'){
					return $this->playerAccountPostProfile($this->player_id);
				}
				break;
			case 'bank-accounts':
				if($request_method == 'GET') {
					return $this->getPlayerBankAccountsByPlayerId($this->player_id);
				}
				else if($request_method == 'POST') {
					if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0) && empty($append)) {
						return $this->updatePlayerBankAccountsById($this->player_id, $additional);
					}
					else if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0) && $append == 'delete') {
						return $this->deletePlayerBankAccountsById($this->player_id, $additional);
					}
					else if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0) && $append == 'set-default') {
						return $this->setPlayerBankAccountAsDefault($this->player_id, $additional);
					}
					else if($additional == 'create') {
						return $this->addNewPlayerBankAccountByPlayerId($this->player_id);
					}
				}
				break;
			case 'clean-token':

				break;
			case 'contact-preferences':
				if($request_method == 'GET') {
					return $this->getPlayerContactPreferencesByPlayerId($this->player_id);
				}
				else if($request_method == 'POST') {
					return $this->updatePlayerContactPreferencesByPlayerId($this->player_id);
				}
				break;
			case 'email':
				if($request_method == 'GET') {
					return $this->getPlayerEmailByPlayerId($this->player_id);
				}
				break;
			case 'email-verification':
				if($request_method == 'POST') {
					if($additional == 'send') {
						return $this->sendVerificationEmailToPlayer($this->player_id);
					}
					return $this->sendOtpCodeToVerifyPlayerEmail($this->player_id);
				}
				break;
			case 'identity':
				break;
			case 'info':
				return $this->getPlayerInfoByPlayerId($this->player_id);
				// $result=['code'=>self::CODE_OK];
				// $result['data']=$this->_mockDataForPlayerapi();
				// return $this->returnSuccessWithResult($result);
				break;
			case 'forgot':
				if($request_method == 'POST') {
					if($additional == 'password') {
						if($append == 'withdrawal') {
							return $this->sendOtpCodeToSetNewPassword('forgot', 'withdrawal', $this->player_id);
						}
						else {
							return $this->sendOtpCodeToSetNewPassword('forgot', 'login');
						}
					}
				}
				break;
			case 'change':
				if($request_method == 'POST') {
					if($additional == 'password') {
						if($append == 'withdrawal') {
							return $this->sendOtpCodeToSetNewPassword('change', 'withdrawal', $this->player_id);
						}
						else {
							return $this->sendOtpCodeToSetNewPassword('change', 'login', $this->player_id);
						}
					}
				}
				break;
			case 'phone-verification':
				if($request_method == 'POST') {
					return $this->sendOtpCodeToVerifyPlayerPhoneNumber($this->player_id);
				}
				break;
			case 'referral-list':
				return $this->getPlayerReferralList($this->player_id);
				break;
			case 'referral-statistics':
				return $this->getPlayerReferralStatistics($this->player_id);
				break;
			case 'stats':
				if($request_method == 'GET') {
					return $this->getPlayerStatsByPlayerId($this->player_id);
				}
				break;
			case 'switch-currency':
				break;
			case 'verification-question':
				break;
			case 'withdraw-password':
				break;
			case 'verification-questions':
				break;
			// sms otp
			case 'otp':
				if($request_method == 'POST') {
					return $this->sendOtpCodeToPlayer('player_id', $this->player_id);
				}
				break;
			case 'otp-public':
				if($request_method == 'POST') {
					return $this->sendOtpCodeToPlayer('player_username');
				}
				break;
            case 'auth-otp':
                if($request_method == 'POST') {
                    return $this->sendAuthOtpCode();
                }
                break;
			case 'login':
				break;
			case 'captcha-image':
				if($request_method == 'GET') {
					return $this->getCaptchaImage();
				}
				break;
			case 'upload-avatar':
				if($request_method == 'POST') {
					return $this->postAvatarUpload();
				}
					break;
			case 'vip-list':
				if($request_method == 'GET') {
					return $this->getVipGroupList($this->player_id);
				}
				break;
			case 'vip-public':
				if($request_method == 'GET') {
					return $this->getVipGroupList();
				}
				break;
			case 'runtime-info':
				if($request_method == 'GET') {
					return $this->getPlayerRuntimeInfo($this->player_id);
				}
				break;
			case 'rank-list-public':
				if($request_method == 'GET') {
					return $this->getPublicRankList();
				}
				break;
			case 'rank-records-public':
				if($request_method == 'GET') {
					return $this->getPublicRankRecords();
				}
				break;
			case 'rank-info':
				if($request_method == 'GET') {
					return $this->getPlayerRankInfo($this->player_id);
				}
				break;
		}
		//unknown
		$this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function getPlayerBankAccountsByPlayerId($playerId) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$bank_details = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId) {
			$options['only_banktype_active'] = true;
			return $this->playerbankdetails->getNotDeletedBankInfoList($playerId, $options);
		});
		// $withdrawal_bank_details = $bank_details['withdrawal'];
		// $deposit_bank_details = $bank_details['deposit'];
		$merge_bank_details = array_merge($bank_details['withdrawal'], $bank_details['deposit']);
		$output = [];
		foreach ($merge_bank_details as $key => $bank_item) {
			$output[$key]['accountHolderName'] = $bank_item['bankAccountFullName'];
			$output[$key]['accountNumber'] = $bank_item['bankAccountNumber'];
			$output[$key]['accountType'] = $this->playerapi_lib->matchOutputAccountType($bank_item['dwBank']);
			$output[$key]['bankBranch'] = $bank_item['branch'];
			$output[$key]['branchAddress'] = $bank_item['bankAddress'];
			$output[$key]['bankId'] = $bank_item['bankTypeId'];
			$output[$key]['defaultAccount'] = (bool)$bank_item['isDefault'];
			$output[$key]['id'] = $bank_item['playerBankDetailsId'];
			$output[$key]['province'] = $bank_item['province'];
			$output[$key]['city'] = $bank_item['city'];
			$output[$key]['phoneNumber'] = $bank_item['phone'];
			$output[$key]['bankType'] = $this->playerapi_lib->matchOutputPaymentTypeFlag($bank_item['payment_type_flag']);
			$output[$key]['editable'] = $this->checkPlayerBankEditable($bank_item['playerBankDetailsId']);
		}
		$output = $this->playerapi_lib->convertOutputFormat($output, ['accountNumber', 'phoneNumber']);
		$result['data'] = $output;
		return $this->returnSuccessWithResult($result);
	}

	protected function updatePlayerBankAccountsById($playerId, $player_bank_detail_id) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		// $request_body = isset($request_body['playerBankAccountForm']) ? $request_body['playerBankAccountForm'] : [];
		$validate_fields = [
			['name' => 'accountHolderName', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'accountNumber', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'bankBranch', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'bankId', 'type' => 'int', 'required' => true, 'length' => 0],
			['name' => 'defaultAccount', 'type' => 'bool', 'required' => false, 'length' => 0],
			['name' => 'province', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'city', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'phoneNumber', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'branchAddress', 'type' => 'string', 'required' => false, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$bank_type_id =  $request_body['bankId'];
		$bank_account_number = $request_body['accountNumber'];
		$bank_account_full_name = $request_body['accountHolderName'];
		$bank_branch =  isset($request_body['bankBranch']) ? $request_body['bankBranch'] : '';
		$is_default_account = isset($request_body['defaultAccount']) ? $request_body['defaultAccount'] : 'no change';
		$province =  isset($request_body['province']) ? $request_body['province'] : '';
		$city =  isset($request_body['city']) ? $request_body['city'] : '';
		$phone =  isset($request_body['phoneNumber']) ? $request_body['phoneNumber'] : '';
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$branchAddress = isset($request_body['branchAddress']) ? $request_body['branchAddress'] : '';
		$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $player_bank_detail_id, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch, $is_default_account, $province, $city, $phone, $branchAddress) {
			return $this->postEditWithdrawal($playerId, $player_bank_detail_id, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch, $is_default_account, $province, $city, $phone, $branchAddress);
		});
		$result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function deletePlayerBankAccountsById($playerId, $player_bank_detail_id) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $player_bank_detail_id) {
			return $this->processDelete($playerId, $player_bank_detail_id);
		});
		$result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function setPlayerBankAccountAsDefault($playerId, $player_bank_detail_id) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $player_bank_detail_id) {
			return $this->processSetDefault($playerId, $player_bank_detail_id);
		});
		$result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function addNewPlayerBankAccountByPlayerId($playerId) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		// $request_body = isset($request_body['playerBankAccountForm']) ? $request_body['playerBankAccountForm'] : [];
		$validate_fields = [
			['name' => 'accountHolderName', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'accountNumber', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'bankBranch', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'bankId', 'type' => 'int', 'required' => true, 'length' => 0],
			['name' => 'defaultAccount', 'type' => 'bool', 'required' => false, 'length' => 0],
			['name' => 'accountType', 'type' => 'int', 'required' => true, 'length' => 0, 'allowed_content' => [1,2]],
			['name' => 'province', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'city', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'phoneNumber', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'otpCode', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'otpSource', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'branchAddress', 'type' => 'string', 'required' => false, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$bank_type_id =  $request_body['bankId'];
		$bank_account_number = $request_body['accountNumber'];
		$bank_account_full_name = $request_body['accountHolderName'];
		$bank_branch =  isset($request_body['bankBranch']) ? $request_body['bankBranch'] : '';
		$is_default_account = $request_body['defaultAccount'];
		$bank_account_type = $request_body['accountType'];
		$province =  isset($request_body['province']) ? $request_body['province'] : '';
		$city =  isset($request_body['city']) ? $request_body['city'] : '';
		$phone =  isset($request_body['phoneNumber']) ? $request_body['phoneNumber'] : '';
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$otp_code =  isset($request_body['otpCode']) ? $request_body['otpCode'] : null;
		$otp_source = isset($request_body['otpSource']) ? $request_body['otpSource'] : null;
		$bank_address = isset($request_body['branchAddress']) ? $request_body['branchAddress'] : '';

		$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch, $is_default_account, $bank_account_type, $province, $city, $phone, $otp_code, $otp_source, $bank_address) {
			return $this->postAddWithdrawal($playerId, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch, $is_default_account, $bank_account_type, $province, $city, $phone, $otp_code, $otp_source, $bank_address);
		});
		$result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function getPlayerContactPreferencesByPlayerId($playerId) {
		$result=['code'=>self::CODE_OK];
		$current_preferences = $this->communication_preference_model->getCurrentPreferences($playerId);
		$output = [];
		$output_contact_preference_list = [];
		if(!empty($current_preferences)) {
			foreach ($current_preferences as $preference_item => $is_prefer) {	//{"email": "true", "sms": "false", "phone_call": "true", "post": "false"}
				if($is_prefer == "true") {
					$this->playerapi_lib->matchOutputContactPreference($preference_item);
					$preference_item_number = $this->playerapi_lib->matchOutputContactPreference($preference_item);
					if(!empty($preference_item_number)) $output_contact_preference_list[] = $preference_item_number;
				}
			}
		}
		$output['contactPreferenceList'] = $output_contact_preference_list;
		$output = $this->playerapi_lib->convertOutputFormat($output);
		$result['data'] = $output;
		return $this->returnSuccessWithResult($result);
	}

	protected function updatePlayerContactPreferencesByPlayerId($playerId) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields_outer = [
			['name' => 'contactPreferenceList', 'type' => 'array[int]', 'required' => true, 'length' => 0, 'allowed_content' => [1,2,4,8]],
		];
		$is_validate_basic_passed =$this->playerapi_lib->validParmasBasic($request_body, $validate_fields_outer);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed for outer key', $is_validate_basic_passed);
		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$contactPreferenceList = isset($request_body['contactPreferenceList']) ? $request_body['contactPreferenceList'] : [];
		$preference_data = $this->playerapi_lib->buildPreferenceData($contactPreferenceList);

		$this->comapi_log(__METHOD__, '=======preference_data', $preference_data);
		$output = $this->updatePreference($playerId, $preference_data);
		$this->comapi_log(__METHOD__, '=======output', $output);

		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
			$result['errorMessage'] = $output['errorMessage'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function getPlayerEmailByPlayerId($playerId) {
		$result=['code'=>self::CODE_OK];
		$player = $this->player_model->getPlayerById($playerId);
		$player_email = !empty($player) && !empty($player->email) ? $player->email : '';
		$result['data']['email'] = $player_email;
		return $this->returnSuccessWithResult($result);
	}

	protected function sendOtpCodeToVerifyPlayerEmail($playerId) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'email', 'type' => 'email', 'required' => true, 'length' => 0],
			['name' => 'otpCode', 'type' => 'string', 'required' => true, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$otp_code =  $request_body['otpCode'];
		$email_address = $request_body['email'];
		$output = $this->setMailVerifiedByOTPCode($playerId, $otp_code, $email_address);
		$result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function sendVerificationEmailToPlayer($playerId) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'email', 'type' => 'email', 'required' => true, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$email_address = $request_body['email'];
		$output = $this->processSendVerificationEmailToPlayer($playerId, $email_address);
		$result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['code'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function sendOtpCodeToSetNewPassword($mode, $set_type, $player_id=null) {
		$this->utils->debug_log(__METHOD__, ' sendOtpCodeToSetNewPassword', $mode, $set_type, $player_id);
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'otpSource', 'type' => 'int', 'required' => true, 'length' => 0, 'allowed_content' => [1,2,3]],
			['name' => 'newPassword', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'otpCode', 'type' => 'string', 'required' => true, 'length' => 0],
		];
		if($mode == 'forgot') {	//use for /public/forgot/password

			if(empty($player_id)){
				$validate_fields[] = ['name' => 'username', 'type' => 'string', 'required' => true, 'length' => 0];
			}

		}
		else if($mode == 'change') {	//use for /player/change/password
			$validate_fields[] = ['name' => 'originalPassword', 'type' => 'string', 'required' => true, 'length' => 0];
		}
		else {
			return $this->returnErrorWithResult($result);
		}

		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$username = isset($request_body['username']) ? $request_body['username'] : '';
		$original_password = isset($request_body['originalPassword']) ? $request_body['originalPassword'] : '';
		$otp_source =  $request_body['otpSource'];
		$new_password = $request_body['newPassword'];
		$otp_code =  $request_body['otpCode'];
		$output = $this->setNewPasswordByOTPCode($mode, $set_type, $player_id, $username, $otp_source, $otp_code, $new_password, $original_password);
		// $result['data'] = $output;
		if($output['success'] == false) {
			$result['code'] = $output['errorCode'];
			$result['errorMessage'] = $output['message'];
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function sendOtpCodeToVerifyPlayerPhoneNumber($playerId) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'countryPhoneCode', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'otpCode', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'phoneNumber', 'type' => 'string', 'required' => true, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$country_code =  $request_body['countryPhoneCode'];
		$otp_code =  $request_body['otpCode'];
		$phone_number = $request_body['phoneNumber'];
		$result = $this->setPhoneNumberVerifiedByOTPCode($playerId, $phone_number, $otp_code);
		// if($result['success'] == false) {
		// 	$result['code'] = $result['errorCode'];
		// }
		return $this->returnSuccessWithResult($result);
	}

	protected function sendOtpCodeToPlayer($mode, $playerId=null) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'otpSource', 'type' => 'int', 'required' => true, 'length' => 0, 'allowed_content' => [Api_common::OTP_SOURCE_SMS,Api_common::OTP_SOURCE_EMAIL,Api_common::OTP_SOURCE_GOOGLE_AUTH]],
			['name' => 'captchaCode', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'captchaKey', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'forcebind', 'type' => 'bool', 'required' => false, 'length' => 0],
		];
		$forceBind = $this->utils->safeGetArray($request_body, 'forceBind', false);
		$otp_source =  $request_body['otpSource'];

		if($mode == 'player_username' && empty($playerId)) {
			$username_required = $forceBind && ($otp_source == Api_common::OTP_SOURCE_SMS) ? false: true;
			$validate_fields[] = ['name' => 'username', 'type' => 'string', 'required' => $username_required, 'length' => 0];
			$validate_fields[] = ['name' => 'target', 'type' => 'string', 'required' => true, 'length' => 0];
			$validate_fields[] = ['name' => 'countryPhoneCode', 'type' => 'string', 'required' => $otp_source == Api_common::OTP_SOURCE_SMS, 'length' => 0];

		}
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$target = null;
		if($mode == 'player_username' && empty($playerId)) {
			$target = $request_body['target'];
			$contact_type = ($otp_source == Api_common::OTP_SOURCE_SMS) ? 'phone' : 'email';
			if($forceBind) {
				if(!empty($request_body['username'])) {
					$player_username = $request_body['username'];
					$get_player_id = $this->getPlayerIdByUsernameAndEmailOrPhone($player_username, $contact_type, $target);
					if(is_array($get_player_id)) {
                        $result['code'] = $get_player_id['errorCode'];
                        $result['errorMessage'] = $get_player_id['errorMessage'];
						return $this->returnErrorWithResult($result);
					}
					else {
						$playerId = $get_player_id;
					}
				}
			} else {
				$player_username = $request_body['username'];
				$get_player_id = $this->getPlayerIdByUsernameAndEmailOrPhone($player_username, $contact_type, $target);
				if(is_array($get_player_id)) {
                    $result['code'] = $get_player_id['errorCode'];
                    $result['errorMessage'] = $get_player_id['errorMessage'];
                    return $this->returnErrorWithResult($result);
				}
				else {
					$playerId = $get_player_id;
				}
			}
		}

		$captcha_code =  $request_body['captchaCode'];
		$captcha_key = $request_body['captchaKey'];
		$check_valid_captcha = $this->checkIsCaptchaValid($playerId, $captcha_code, $captcha_key);
		if($check_valid_captcha['success'] == false) {
			$result['code'] = $check_valid_captcha['errorCode'];
            $result['errorMessage'] = $check_valid_captcha['errorMessage'];
			return $this->returnSuccessWithResult($result);
		}

		$countryPhoneCode = !empty($request_body['countryPhoneCode']) ? $request_body['countryPhoneCode'] : '';
		$result = $this->sendVerificationWithOTPSource($playerId, $otp_source, $target, $forceBind, $countryPhoneCode);

        if(!empty($result['success'])){
			if($this->utils->isEnabledMDB() && $playerId){
				$this->load->model(['multiple_db_model']);
				$this->multiple_db_model->syncPlayerOTPFromCurrentToOtherMDB($playerId, $otp_source);
			}
            $output['code'] = $result['code'];
            $output['successMessage'] = $result['message'];
            return $this->returnSuccessWithResult($output);
		}else{
            $output['code'] = $result['code'];
            $output['errorMessage'] = $result['errorMessage'];
            return $this->returnErrorWithResult($output);
        }
	}

    protected function sendAuthOtpCode() 
    {
        $request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'otpSource', 'type' => 'int', 'required' => true, 'length' => 0, 
                'allowed_content' => [ Api_common::OTP_SOURCE_SMS, Api_common::OTP_SOURCE_EMAIL ]
            ],
            ['name' => 'type', 'type' => 'int', 'required' => false, 'length' => 0, 
                'allowed_content' => [ 
                    Api_common::CODE_AUTH_OTP_TYPE_REGISTER, 
                    Api_common::CODE_AUTH_OTP_TYPE_FORGET_PASSWORD,
                    Api_common::CODE_AUTH_OTP_TYPE_LOGIN,
                ]
            ],
            ['name' => 'target', 'type' => 'string', 'required' => true, 'length' => 0,],
			['name' => 'captchaCode', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'captchaKey', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'captchaToken', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'countryPhoneCode', 'type' => 'string', 'required' => false, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

        $target = $request_body['target'];
        $otp_source = $request_body['otpSource'];
        $type = $request_body['type'];
        $country_phone_code = !empty($request_body['countryPhoneCode']) ? $request_body['countryPhoneCode'] : '';

        if(!empty($target)){
            switch ($otp_source) {
                case Api_common::OTP_SOURCE_SMS:
                    $contact_info['contactNumber'] = $target;            
                    break;
                case Api_common::OTP_SOURCE_EMAIL:
                    $contact_info['email'] = $target;            
                    break;
                default:
                    $contact_info = [];
                    break;
            }
            $player_info = $this->playerapi_model->getUsernameByContactInfo($contact_info);
        }else{
            $result['code'] = Playerapi::CODE_EMPTY_TARGET_CONTRACT_INFO_PARAMETER;
            $result['errorMessage']= $this->codes[Playerapi::CODE_EMPTY_TARGET_CONTRACT_INFO_PARAMETER];
            return $this->returnErrorWithResult($result);
        }

        $_must_existed_target_in_type = [
            Api_common::CODE_AUTH_OTP_TYPE_FORGET_PASSWORD => true,
            Api_common::CODE_AUTH_OTP_TYPE_LOGIN => true,
        ];

        if(isset($_must_existed_target_in_type[$type])){
            //The contact target must be restricted to only one player.
            if(empty($player_info)){
                $result['code'] = Playerapi::CODE_PLAYER_NOT_FOUND;
                $result['errorMessage']= $this->codes[Playerapi::CODE_PLAYER_NOT_FOUND];
                return $this->returnErrorWithResult($result);
            }
            if(count($player_info) !== 1){
                $result['code'] = Playerapi::CODE_DUPLICATED_CONTACT_INFO;
                $result['errorMessage']= $this->codes[Playerapi::CODE_DUPLICATED_CONTACT_INFO];
                return $this->returnErrorWithResult($result);
            }
            $forceBind = false;
            $player_info = array_pop($player_info);
            $username = $player_info['username'];
            $playerId = $player_info['playerId'];
        }else{
            //The contact target must not exist on any player
            if(!empty($player_info)){
                if($otp_source == Api_common::OTP_SOURCE_SMS ){
                    $result['code'] = Playerapi::CODE_DUPLICATED_PHONE_NUMBER;
                    $result['errorMessage']= $this->codes[Playerapi::CODE_DUPLICATED_PHONE_NUMBER];
                    return $this->returnErrorWithResult($result);
                }
                if($otp_source == Api_common::OTP_SOURCE_EMAIL){
                    $result['code'] = Playerapi::CODE_PLAYER_EMAIL_ALREADY_EXISTS;
                    $result['errorMessage']=  $this->codes[Playerapi::CODE_PLAYER_EMAIL_ALREADY_EXISTS];
                    return $this->returnErrorWithResult($result);
                }
            }
            $forceBind = true;
            $playerId = null;
        }

        $captcha_code =  $request_body['captchaCode'];
		$captcha_key = $request_body['captchaKey'];
		$check_valid_captcha = $this->checkIsCaptchaValid($playerId, $captcha_code, $captcha_key);
		if($check_valid_captcha['success'] == false) {
			$result['code'] = $check_valid_captcha['errorCode'];
            $result['errorMessage'] = $check_valid_captcha['errorMessage'];
			return $this->returnSuccessWithResult($result);
		}

		$result = $this->sendVerificationWithOTPSource($playerId, $otp_source, $target, $forceBind, $country_phone_code);
        
		if(!empty($result['success'])){
            $output['code'] = $result['code'];
            $output['successMessage'] = $result['message'];

            if($type == Api_common::CODE_AUTH_OTP_TYPE_FORGET_PASSWORD){
                $output['data'] = ['username' => $username];
            }

			if($this->utils->isEnabledMDB() && $playerId){
				$this->load->model(['multiple_db_model']);
				$this->multiple_db_model->syncPlayerOTPFromCurrentToOtherMDB($playerId, $otp_source);
			}

            return $this->returnSuccessWithResult($output);
		}else{
            $output['code'] = $result['code'];
            $output['errorMessage'] = $result['errorMessage'];

            return $this->returnErrorWithResult($output);
        }
	}
    
	protected function playerAccountRegister($additional = null){
		try {
            $request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);

			//OGP-33436
			if (static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'register')) {
				$username = !empty($request_body['username'])? $request_body['username'] : '';
			
				$this->utils->debug_log('block login on api login', $username, $this->utils->tryGetRealIPWithoutWhiteIP());
				$result['code'] = self::CODE_IP_RESTRICTED;
				$result['errorMessage']= 'IP is restricted.';
				return $this->returnErrorWithResult($result);
			}			

            if($additional == 'phone'){
                $request_body = $this->playerAccountRegisterWithPhone($request_body);
            }

			$is_captcha_enabled = ($this->utils->getConfig('enabled_registration_captcha') && $this->operatorglobalsettings->getSettingJson('registration_captcha_enabled'));
			if($is_captcha_enabled){
				$valid_captcha = $this->handleValidateCaptcha();
				if(!$valid_captcha['passed']){
					throw new APIException($valid_captcha['error_message'], $valid_captcha['code']);
				}
			}
			$allowedCountryPhoneCode = $this->allowedCountryPhoneCode();
			$allowedCountryCode = $this->allowedCountryCode();
			$allowedCountryIsoLang = $this->allowedCountryIsoLang();
			$validate_fields = [
				['name' => 'username', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'password', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'referralCode', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'affTrackingCode', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'affTrackingSourceCode', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'language', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => $allowedCountryIsoLang],
				['name' => 'currency', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'acceptPromotionEmail', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'agentTrackingCode', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'appVersion', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'marketingChannel', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'withdrawPassword', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'countryCode', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => $allowedCountryCode],
				['name' => 'residentCountry', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => $allowedCountryCode],
				['name' => 'region', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'city', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'address', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'address2', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'zipcode', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'firstName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'lastName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'middleName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'maternalName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'birthday', 'type' => 'date', 'required' => false, 'length' => 0],
				['name' => 'birthplace', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'gender', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => ['M', 'F']],
				['name' => 'email', 'type' => 'email', 'required' => false, 'length' => 0],
				['name' => 'countryPhoneCode', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => $allowedCountryPhoneCode],
				['name' => 'phoneNumber', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'otpCode', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im1', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im2', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im3', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im4', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im5', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'cpfNumber', 'type' => 'string', 'required' => false, 'length' => 11],
				['name' => 'idCardNumber', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'idCardType', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'issuingLocation', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'issuanceDate', 'type' => 'date', 'required' => false, 'length' => 0],
				['name' => 'isPEP', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'acceptCommunications', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'bankName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'bankAccountNumber', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'bankAccountName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'secretQuestion', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'secretAnswer', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'newsletterSubscription', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'ageRestrictions', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'player_preference_email', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'player_preference_sms', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'player_preference_phone_call', 'type' => 'bool', 'required' => false, 'length' => 0],
				['name' => 'player_preference_post', 'type' => 'bool', 'required' => false, 'length' => 0],
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				$result['code'] = self::CODE_INVALID_PARAMETER;
				$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
				return $this->returnErrorWithResult($result);
			}
			$post_data = $this->generateRegisterPostData($request_body);


			$result = $this->postRegisterPlayerAccount($post_data);

			if($result['success']){
				$output['code'] = self::CODE_OK;
				$output['successMessage'] = $result['message'];
				return $this->returnSuccessWithResult($output);
			}else{
				throw new APIException($result['errorMessage'], $result['errorCode']);
			}

		}catch (\APIException $ex) {
	        $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
	        $this->comapi_log(__METHOD__, 'APIException', $result);

	        return $this->returnErrorWithResult($result);
	    }

	}

    protected function playerAccountRegisterWithPhone($request_body){

        $validate_fields = [
            ['name' => 'password', 'type' => 'string', 'required' => true, 'length' => 0],
            ['name' => 'phoneNumber', 'type' => 'string', 'required' => true, 'length' => 0],
        ];
        $is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
        $this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);
        if(!$is_validate_basic_passed['validate_flag']) {
            throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
        }

        $_phoneNumber = $request_body['phoneNumber'];
        $_result = !$this->player_model->checkContactExist($_phoneNumber);
        if (!$_result) {
            // The contact number has been used
            $_code = self::CODE_DUPLICATED_PHONE_NUMBER;
            $_error_message = $this->codes[self::CODE_DUPLICATED_PHONE_NUMBER];
            throw new APIException($_error_message, $_code);
        }else{
            $request_body['phoneNumber'] = $_phoneNumber;
        }
        if( empty($request_body['username']) ){
            $max_username_length = $this->utils->getConfig('default_max_size_username');
            $request_body['username'] = strtolower(random_string('alnum', $max_username_length - 1)).random_string('numeric', 1);
        }
        return $request_body;
    } // EOF playerAccountRegisterWithPhone

	protected function playerAccountPostProfile($playerId){
		try {
			$result=['code'=>self::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);

			$allowedCountryCode = $this->allowedCountryCode();
			$allowedCountryPhoneCode = $this->allowedCountryPhoneCode();
			$allowedCountryIsoLang = $this->allowedCountryIsoLang();
			// $request_body = isset($request_body['playerBankAccountForm']) ? $request_body['playerBankAccountForm'] : [];
			$validate_fields = [
				['name' => 'address', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'birthday', 'type' => 'date', 'required' => false, 'length' => 0],
				['name' => 'city', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'countryCode', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => $allowedCountryCode],
				['name' => 'countryPhoneCode', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => $allowedCountryPhoneCode],
				['name' => 'firstName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'gender', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => ['M', 'F']],
				['name' => 'language', 'type' => 'string', 'required' => true, 'length' => 0, 'allowed_content' => $allowedCountryIsoLang],
				['name' => 'lastName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'line', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'phoneNumber', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'email', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im1', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im2', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'im3', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'avatarUrl', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'cpfNumber', 'type' => 'string', 'required' => false, 'length' => 11],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			//profile key reference column names in player and playerdetails.
			$profile_data = [];

			if(isset($request_body['address'])){
				$profile_data['address'] = $request_body['address'];
			}

			if(isset($request_body['birthday'])){
				$profile_data['birthdate'] = $request_body['birthday'];
			}

			if(isset($request_body['city'])){
				$profile_data['city'] = $request_body['city'];
			}

			if(isset($request_body['countryCode'])){
				$profile_data['citizenship'] = $this->playerapi_lib->matchInputCountryCode($request_body['countryCode']);
			}

			if(isset($request_body['countryPhoneCode'])){
				$profile_data['dialing_code'] = $request_body['countryPhoneCode'];
			}

			if(isset($request_body['firstName'])){
				$profile_data['firstName'] = trim($request_body['firstName']);
			}

			if(isset($request_body['lastName'])){
				$profile_data['lastName'] = trim($request_body['lastName']);
			}

			if(isset($request_body['gender'])){
				$profile_data['gender'] = $this->playerapi_lib->matchInputGender($request_body['gender']);
			}

			$profile_data['language'] = $this->playerapi_lib->matchInputLanguage($request_body['language']);

			if(isset($request_body['phoneNumber'])){
				$profile_data['contactNumber'] = $request_body['phoneNumber'];
			}

			if(isset($request_body['email'])){
				$profile_data['email'] = $request_body['email'];
			}

			if(isset($request_body['im1'])){
				$profile_data['imAccount'] = $request_body['im1'];
			}

			if(isset($request_body['im2'])){
				$profile_data['imAccount2'] = $request_body['im2'];
			}

			if(isset($request_body['im3'])){
				$profile_data['imAccount3'] = $request_body['im3'];
			}

			if(isset($request_body['avatarUrl'])){
				$profile_data['avatarUrl'] = $request_body['avatarUrl'];
			}

			if(isset($request_body['cpfNumber'])){
				$profile_data['pix_number'] = $request_body['cpfNumber'];
			}

			// #region - these fields are not yet implemented.
			if(isset($request_body['region'])){
				$profile_data['region'] = $request_body['region'];
			}

			if(isset($request_body['address2'])){
				$profile_data['address2'] = $request_body['address2'];
			}

			if(isset($request_body['address3'])){
				$profile_data['address3'] = $request_body['address3'];
			}

			if(isset($request_body['zipcode'])){
				$profile_data['zipcode'] = $request_body['zipcode'];
			}

			if(isset($request_body['birthplace'])){
				$profile_data['birthplace'] = $request_body['birthplace'];
			}

			if(isset($request_body['residentCountry'])){
				$profile_data['residentCountry'] = $request_body['residentCountry'];
			}

			if(isset($request_body['id_card_number'])){
				$profile_data['id_card_number'] = $request_body['id_card_number'];
			}

			if(isset($request_body['id_card_type'])){
				$profile_data['id_card_type'] = $request_body['id_card_type'];
			}

			if(isset($request_body['imAccount4'])){
				$profile_data['imAccount4'] = $request_body['imAccount4'];
			}

			if(isset($request_body['sms_verification_code'])){
				$profile_data['sms_verification_code'] = $request_body['sms_verification_code'];
			}
			// #end region

			$result = $this->postEditPlayerAccountProfile($playerId, $profile_data);

			$pixSystemInfo = $this->utils->getConfig('pix_system_info');
			if($pixSystemInfo['auto_build_pix_account']['enabled'] && $result['success']){
				$bank_details_ids = $this->autoBuildPlayerPixAccount($playerId);
				$this->utils->debug_log("============auto_build_pix_account_type", $bank_details_ids);
			}

			if($result['success']){
				$output['code'] = self::CODE_OK;
				$output['successMessage'] = $result['message'];
				return $this->returnSuccessWithResult($output);
			}else{
				throw new APIException($result['errorMessage'], $result['errorCode']);
			}

		}catch (\APIException $ex) {
	        $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
	        $this->comapi_log(__METHOD__, 'APIException', $result);

	        return $this->returnErrorWithResult($result);
	    }
	}

	private function getCountryCodeByPhoneCode($dialing_code){
		if(!empty($dialing_code)){
			$countryCode = $this->playerapi_lib->getCountryNameByPhoneCode($dialing_code);
		}else{
			$countryCode = '';
		}
		return $countryCode;
	}

	protected function playerAccountGetProfile($playerId){
		$result = [
            'code' => self::CODE_OK,
            'data' => null,
        ];

		$playerProfile = $this->playerapi_model->getPlayerProfileByPlayerId($playerId);
        $verifyFunctionList = [
			[ 'name' => 'verifyPasswordNotEmpty', 'params' => [$playerProfile['password']] ],
		];

		foreach ($verifyFunctionList as $method) {
			$this->utils->debug_log('============getPlayerProfile verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
			if(!$exec_continue) {
				$result['code'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $this->returnErrorWithResult($result);
			}
		}

		$playerProfile['playerId'] = (string) $playerId;
		$playerProfile['campaignEnabled'] = (bool)$playerProfile['campaignEnabled'];
		$playerProfile['cashbackEnabled'] = (bool)$playerProfile['cashbackEnabled'];
		$playerProfile['withdrawEnabled'] = (bool)$playerProfile['withdrawEnabled'];
		$playerProfile['emailVerified']   = (bool)$playerProfile['emailVerified'];
		$playerProfile['phoneVerified']   = (bool)$playerProfile['phoneVerified'];
		$playerProfile['restriction']     = $this->playerapi_lib->getRestrictionByPlayerId($playerId);
		$playerProfile['withdrawalPasswordExists'] = (!empty($playerProfile['withdrawalPasswordExists']));
        $playerProfile['isPEP'] = (bool)$playerProfile['isPEP'];
        $playerProfile['isInterdicted'] = (bool)$playerProfile['isInterdicted'];
        $playerProfile['isInjunction'] = (bool)$playerProfile['isInjunction'];
        $playerProfile['acceptCommunications'] = (bool)$playerProfile['acceptCommunications'];    
        $playerProfile['cpfVerified'] = false;  
        $pixSystemInfo = $this->utils->getConfig('pix_system_info');

		if($pixSystemInfo['identify_cpf_numer_on_kyc']['enabled']){
			$playerProfile['cpfVerified'] = (bool)$this->verifyCpfStatusOnKyc($playerId);
		}

		$playerProfile['avatarUrl'] = $this->setProfilePicture($playerId);
		$rebuildKeyArr = ['createdAt', 'referer_player_id', 'country_code', 'language', 'communication_preference', 'region'];
		$visableCheckList = ['im1'=>'imAccount',
							   'im2'=>'imAccount2',
							   'im3'=>'imAccount3'
							];

		$this->playerapi_lib->filterPlayerProfileVisable($playerProfile, $visableCheckList);
		$output = $this->playerapi_lib->customizeApiOutput([$playerProfile], $rebuildKeyArr);
		$output = $this->playerapi_lib->convertOutputFormat($output, ['countryCode', 'countryPhoneCode', 'phoneNumber', 'vipCode', 'cpfNumber', 'playerId']);
        if(!empty($output) && is_array($output) && count($output) == 1){
            $result['data'] = array_pop($output);
        }
		return $this->returnSuccessWithResult($result);
	}

	protected function getPlayerInfoByPlayerId($playerId){
		$result=['code'=>self::CODE_OK];
		$player_info = $this->playerapi_model->getPlayerInfoByPlayerId($playerId);
		$player_info['username_on_register'] = $this->player_functions->get_username_on_register($playerId);
		$player_info['avatar'] = $this->setProfilePicture($playerId);
		$player_info['lastLogin']['ip'] = $player_info['lastLoginIp'];
		$player_info['lastLogin']['time'] = $player_info['lastLoginTime'];
		$player_info['group']['id'] = $player_info['vip_level_id'];
		$player_info['group']['name'] = lang($player_info['vip_group_name']). ' - '.lang($player_info['vip_level_name']);
		$badge = $this->utils->imageUrl('vip_badge/' ."vip-icon.png");
		if(file_exists($this->utils->getVipBadgePath().$player_info['badge'])) {
			$badge = $this->utils->getVipBadgeUri().'/'.$player_info['badge'];
		}
		$player_info['group']['imageUrl'] = $badge;
		$player_info['phoneVerified'] = (bool)$player_info['phoneVerified'];
		$player_info['emailVerified'] = (bool)$player_info['emailVerified'];
		$player_info['withdrawPasswordExists'] = (bool)$player_info['withdrawPasswordExists'];
		$player_info['passwordQuestionExists'] = (bool)$player_info['passwordQuestionExists'];
		$player_info['passwordPending'] = false;
		$player_info['unreadMessageCount'] = $this->utils->unreadMessages($playerId);
		unset($player_info['lastLoginIp']);
		unset($player_info['lastLoginTime']);
		unset($player_info['vip_level_id']);
		unset($player_info['vip_group_name']);
		unset($player_info['vip_level_name']);
		unset($player_info['badge']);
		$active_currency_key = $this->utils->getActiveCurrencyKeyOnMDB();
		$player_info['currency'] = strtoupper($active_currency_key);
        $player_info['profileProgress'] = $this->getProfileProgress($playerId);
		$output = $this->playerapi_lib->convertOutputFormat($player_info);
		$result['data'] = $output;
		return $this->returnSuccessWithResult($result);
	}

	protected function getPlayerStatsByPlayerId($playerId) {
		$result=['code'=>self::CODE_OK];
		$output = $this->playerapi_model->getPlayerStatsByPlayerId($playerId);
		$output = $this->playerapi_lib->convertOutputFormat($output);
		$result['data'] = $output;
		return $this->returnSuccessWithResult($result);
	}

	protected function getCaptchaImage() {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$validate_fields = [
			['name' => 'imageWidth', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'imageHeight', 'type' => 'int', 'required' => false, 'length' => 0],
		];
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}
		$image_width =  isset($request_body['imageWidth']) ? $request_body['imageWidth'] : 110;
		$image_height =  isset($request_body['imageHeight']) ? $request_body['imageHeight'] : 40;
		$captha_data = $this->generateCaptcha($image_width, $image_height);
		$result['data'] = $captha_data;
		return $this->returnSuccessWithResult($result);
	}

	protected function getPlayerReferralList($playerId)
	{
		$this->load->model(['player_friend_referral', 'player_model']);

		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => null
		];
		try {
			$request_body = $this->playerapi_lib->getRequestPramas();
			$validate_fields = [
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'from', 'type' => 'date-time', 'required' => false, 'length' => 0],
				['name' => 'to', 'type' => 'date-time', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
				$result['errorMessage'] = $is_validate_basic_passed['validate_msg'];
				return $this->returnErrorWithResult($result);
			}

			/** @var int $page */
			$page = (isset($request_body['page'])) ? $request_body['page'] : 1;

			/** @var int $limit */
			$limit = (isset($request_body['limit'])) ? $request_body['limit'] : 20;

			/** @var string $from */
			$from = (isset($request_body['from'])) ? $request_body['from'] : null;

			/** @var string $to */
			$to = (isset($request_body['to'])) ? $request_body['to'] : null;

			$result['data'] = $this->player_friend_referral->getPlayerReferralPagination($playerId, null, $from, $to, $limit, $page);
			$result['data']['list'] = $this->playerapi_lib->customizeApiOutput($result['data']['list'], ['invitedOn']);
			$result['data']['list'] = $this->playerapi_lib->convertOutputFormat($result['data']['list']);

			return $this->returnSuccessWithResult($result);
		} catch (\Throwable $th) {

		}
	}

	protected function getPlayerReferralStatistics($playerId)
	{
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => null
		];
		try {
			$this->load->model(['player_model', 'player_friend_referral', 'friend_referral_settings']);
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__. '=======request_body', $request_body);
			$currency = !empty($request_body['currency']) ? $request_body['currency'] : $this->currency;
			$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $playerId){

				$checkPlayerId = $this->player_model->getPlayerArrayById($playerId) ?: false;
				$referralCode = $checkPlayerId['invitationCode'];
				
				$invitedCount = 0;
				$availableReferralCount = 0;
				$accumulatedBonuses = 0;

				$referral = $this->player_friend_referral->getPlayerReferralList($playerId);
				if(!empty($referral)){
					$invitedCount = count($referral);
				}

				$availableReferral = $this->player_friend_referral->getPlayerReferralList($playerId, Player_friend_referral::STATUS_PAID);
				if(!empty($availableReferral)){
					$availableReferralCount = count($availableReferral);
				}

				$settings = $this->friend_referral_settings->getFriendReferralSettings();
				$inviteBonus = isset($settings['bonusAmount']) ? $settings['bonusAmount'] : 0;
				$accumulatedBonuses = $this->player_friend_referral->getTotalReferralBonusByPlayerId($playerId);

				$bonusCurrency = [
					'accumulatedBonuses' => [[
						'currency' => strtoupper($currency),
						'amount' => floatval($accumulatedBonuses) ?: 0
					]],
					'inviteBonus' => [[
						'currency' => strtoupper($currency),
						'amount' => floatval($inviteBonus)
					]]
				];

				$output = [
					"referralCode" => $referralCode,
					"invitedCount"  => $invitedCount ?: 0,
					"availableReferralCount" => $availableReferralCount ?: 0,
					"accumulatedBonuses" => floatval($accumulatedBonuses) ?: 0,
					"inviteBonus" => floatval($inviteBonus),
					"bonusCurrency" => $bonusCurrency,
				];

				return $output;
			});

			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $output;
			return $this->returnSuccessWithResult($result);
		} catch (\Throwable $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
		}
	}

	protected function getVipGroupList($playerId = null)
	{
		try {
			$active_currency_key = $this->utils->getActiveCurrencyKeyOnMDB();
			$wrapped_group_info = $this->playerapi_lib->switchCurrencyForAction($active_currency_key, function() use ($playerId) {
				$vip_additional = ['vip_upgrade_setting' => true] ;

				if(empty($playerId)){
					$default_level = $this->utils->getConfig('default_level_id');
					$default_group = $this->playerapi_model->getVipGroupLevelDetails($default_level);
					$group_id = $default_group['vipSettingId'];
					$vip_additional['default_level_id'] = $default_level;
					$vip_additional['default_group_id'] = $group_id;
				}
				$group_list = $this->getCurrentVipGroupInfo($playerId, $vip_additional);

				if(empty($group_list)){
					throw new APIException('Vip Group not found.', Playerapi::CODE_VIP_GROUP_SETTING_ERROR);
				}

				$wrapped_group_info = [];
				foreach ($group_list as $group_item) {
					$result['vipName']  			= lang($group_item['groupName']) . ' ' . lang($group_item['vipLevelName']);
					$result['vipCode'] 				= $group_item['vipsettingcashbackruleId'];
					$result['vipLevel'] 		    = $group_item['vipLevel'];
					$result['upgrade']['type']      = $group_item['vip_upgrade']['type'];
					$result['upgrade']['formula']   = $this->vipListFormat($group_item['vip_upgrade']['formula']);
					$result['downgrade']['type']    = $group_item['vip_downgrade']['type'];
					$result['downgrade']['formula'] = $this->vipListFormat($group_item['vip_downgrade']['formula']);
					$wrapped_group_info[]= $result;
				}

				return $wrapped_group_info;
			});

			$output['code'] = self::CODE_OK;
			$output['data'] = $wrapped_group_info;
			return $this->returnSuccessWithResult($output);
		} catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
	}

	protected function getPlayerViplList($playerId)
	{
		try {
			$active_currency_key = $this->utils->getActiveCurrencyKeyOnMDB();
			$result = $this->playerapi_lib->switchCurrencyForAction($active_currency_key, function() use ($playerId) {
				$vip_additional = ['vip_upgrade_setting' => true] ;
				$player_vip_list = $this->getCurrentVipLevelInfo($playerId, $vip_additional);

				if(empty($player_vip_list)){
					throw new APIException('Player not found.', Playerapi::CODE_PLAYER_NOT_FOUND);
				}

				$result['vipName']  			= $player_vip_list['groupName'].$player_vip_list['vipLevelName'];
				$result['vipCode'] 				= $player_vip_list['levelId'];
				$result['vipLevel'] 		    = $player_vip_list['vipLevel'];
				$result['upgrade']['type']      = $player_vip_list['vip_upgrade']['type'];
				$result['upgrade']['formula']   = $this->vipListFormat($player_vip_list['vip_upgrade']['formula']);
				$result['downgrade']['type']    = $player_vip_list['vip_downgrade']['type'];
				$result['downgrade']['formula'] = $this->vipListFormat($player_vip_list['vip_downgrade']['formula']);

				return $result;
			});

			$output['code'] = self::CODE_OK;
			$output['data'] = $result;
			return $this->returnSuccessWithResult($output);
		} catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
	}

	private function vipListFormat($data)
	{
		if(empty($data) || !is_array($data)){
			return '';
		}
		$new_data=[];
		foreach($data as $key => $value){
			$filter_key=str_replace("_","",$key);
			$new_key=str_replace("amount","Amount",$filter_key);
			$new_data[$new_key]=$value;
		}
		return $new_data;
	}

	protected function postAvatarUpload() {
		if (!$this->initApi()) {
			return;
		}
		$validate_fields = [
			['name' => 'file', 'type' => 'file', 'required' => true],
		];
		$result = ['code' => Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$image = $_FILES['file'];
		$this->comapi_log("=====================".__METHOD__, 'files', $_FILES);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$player_id = $this->player_id;
		$result = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $image) {
			return $this->handleAvatarFileUpload($player_id, $image);
		});

		return $this->returnSuccessWithResult($result);
	}

	private function handleAvatarFileUpload($player_id, $image) {
		$this->utils->debug_log("============handleAvatarFileUpload start {$player_id}", $image);
		$result = [
			'code' => self::CODE_AVATAR_UPLOAD_FAILED,
		];

		$verify_function_list = [
			[ 'name' => 'verifyEnableUploadAvatarFile', 'params' => []],
		];

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============handleAvatarFileUpload verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['data']['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $result;
			}
		}

		$this->load->model(array('player_attached_proof_file_model'));
		$input = array(
			'player_id' => $player_id,
			'tag' => player_attached_proof_file_model::PROFILE_PICTURE,
		);

		$data = [
			'input' => $input,
			'image' => $image
		];

		$upload_resp = $this->player_attached_proof_file_model->upload_proof_document($data);
		$this->comapi_log("=====================".__METHOD__, 'upload_resp', $upload_resp, $data);

		if(!empty($upload_resp)){
			if($upload_resp['status'] == "success"){
				$result['code'] = self::CODE_OK;
				$result['data']['message'] = lang('Attachment uploaded successfully');
			} else {
				$result['data']['message'] = lang("Error uploading file").': '.$upload_resp['msg'];
				$result['errorMessage'] = lang("Error uploading file").': '.$upload_resp['msg'];
			}
		}

		return $result;
	}

	private function getPublicRankList() {
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => null
		];
		try {
			$this->load->model(['player_score_model']);
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__. '=======request_body', $request_body);
			$validate_fields = [
				['name' => 'rankType', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'syncDate', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'currency', 'type' => 'string', 'required' => false, 'length' => 0]
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
				$result['errorMessage'] = $is_validate_basic_passed['validate_msg'];
				return $this->returnErrorWithResult($result);
			}

			$rank_type = isset($request_body['rankType']) ? $request_body['rankType'] : 'newbet';
			$rank_setting = $this->player_score_model->checkCustomRank($rank_type);
			$this->comapi_log(__METHOD__." =======rank_type [$rank_type]", $rank_setting);
			if(empty($rank_setting)){
				throw new APIException('Rank not found.', Playerapi::CODE_RANK_TYPE_NOT_FOUND);
			}

			if(!empty($request_body['currency'])){
				$active_currency_key = $request_body['currency'];
			}else{
				$active_currency_key = $this->utils->safeGetArray($rank_setting, 'fallback_currency', $this->utils->getActiveCurrencyKeyOnMDB());
			}

			$output = $this->playerapi_lib->switchCurrencyForAction($active_currency_key, function() use ($rank_type, $rank_setting){
				$rank_list = $this->getRankList($rank_type, $rank_setting);
				$rank_list = $this->playerapi_lib->convertOutputFormat($rank_list);
				return $rank_list;
			});
			$output['currency'] = strtoupper($active_currency_key);
			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $output;
			return $this->returnSuccessWithResult($result);
		}
		catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);

			return $this->returnErrorWithResult($result);
		}
	}
	private function getPublicRankRecords() {
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => null
		];
		try {
			$this->load->model(['player_score_model']);
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__. '=======request_body', $request_body);
			$validate_fields = [
				['name' => 'rankType', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'syncDate', 'type' => 'string', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
				$result['errorMessage'] = $is_validate_basic_passed['validate_msg'];
				return $this->returnErrorWithResult($result);
			}

			$rank_type = isset($request_body['rankType']) ? $request_body['rankType'] : 'newbet';
			$rank_setting = $this->player_score_model->checkCustomRank($rank_type);
			$this->comapi_log(__METHOD__." =======rank_type [$rank_type]", $rank_setting);
			if(empty($rank_setting)){
				throw new APIException('Rank not found.', Playerapi::CODE_RANK_TYPE_NOT_FOUND);
			}

			$active_currency_key = $this->utils->safeGetArray($rank_setting, 'fallback_currency', $this->utils->getActiveCurrencyKeyOnMDB());
			$output = $this->playerapi_lib->switchCurrencyForAction($active_currency_key, function() use ($rank_type, $rank_setting){
				$rank_list = $this->getRankRecords($rank_type, $rank_setting);
				$rank_list = $this->playerapi_lib->convertOutputFormat($rank_list);
				return $rank_list;
			});
			$output['currency'] = strtoupper($active_currency_key);
			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $output;
			return $this->returnSuccessWithResult($result);
		}
		catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);

			return $this->returnErrorWithResult($result);
		}
	}
	private function getPlayerRankInfo($player_id) {
		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => null
		];
		try {
			$this->load->model(['player_score_model']);
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__. '=======request_body', $request_body);
			$validate_fields = [
				['name' => 'rankType', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'syncDate', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'currency', 'type' => 'string', 'required' => false, 'length' => 0],
			];
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if (!$is_validate_basic_passed['validate_flag']) {
				$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
				$result['errorMessage'] = $is_validate_basic_passed['validate_msg'];
				return $this->returnErrorWithResult($result);
			}

			$rank_type = isset($request_body['rankType']) ? $request_body['rankType'] : 'newbet';
			$rank_setting = $this->player_score_model->checkCustomRank($rank_type);
			$this->comapi_log(__METHOD__." =======rank_type [$rank_type]", $rank_setting);
			if(empty($rank_setting)){
				throw new APIException('Rank not found.', Playerapi::CODE_RANK_TYPE_NOT_FOUND);
			}
			if(empty($player_id)){
				throw new APIException('Player not found.', Playerapi::CODE_PLAYER_NOT_FOUND);
			}
			if(!empty($request_body['currency'])){
				$active_currency_key = $request_body['currency'];
			}else{
				$active_currency_key = $this->utils->safeGetArray($rank_setting, 'fallback_currency', $this->utils->getActiveCurrencyKeyOnMDB());
			}
			$output = $this->playerapi_lib->switchCurrencyForAction($active_currency_key, function() use ($rank_type, $rank_setting, $player_id){
				$rank_list = $this->getPlayerRanking($player_id, $rank_type, $rank_setting);
				$rank_list = $this->playerapi_lib->convertOutputFormat($rank_list);
				return $rank_list;
			});
			$output['currency'] = strtoupper($active_currency_key);
			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $output;
			return $this->returnSuccessWithResult($result);
		}
		catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);

			return $this->returnErrorWithResult($result);
		}
	}

    private function getPlayerRuntimeInfo($player_id)
	{
		try {
			$active_currency_key = $this->utils->getActiveCurrencyKeyOnMDB();
			$output = $this->playerapi_lib->switchCurrencyForAction($active_currency_key, function() use ($player_id) {
				$player_vip_group_runtime_info = $this->getVIPCurrentTotalDepositAndBets($player_id);
				$result['playerTotalDeposit']  = $player_vip_group_runtime_info['playerTotalDeposit'];
				$result['playerTotalBetting']  = $player_vip_group_runtime_info['playerTotalBetting'];
				return $result;
			});

            $result['code'] = Playerapi::CODE_OK;
            $result['data'] = $this->playerapi_lib->convertOutputFormat($output);
            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }
}
