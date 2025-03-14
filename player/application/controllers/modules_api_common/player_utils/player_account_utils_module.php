<?php

trait player_account_utils_module {

	protected function postAddWithdrawal($playerId, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch, $is_default_account, $bank_account_type, $province, $city, $phone, $otp_code, $otp_source, $bank_address) {
		$input_banktype_id = $bank_type_id;
		$player = $this->player_model->getPlayerInfoDetailById($playerId);
		$banktype = null;
		$payment_type_flag = null;
		$oldBankAccountFullName = Player::getPlayerFullName($player['firstName'], $player['lastName'], $player['language']);
		$newBankAccountFullName = $bank_account_full_name;

		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		$verify_allow_add_bank_list = [];
		switch ($bank_account_type) {
			case 1:
				$all_banks = $this->playerbankdetails->getDepositBankDetail($playerId);
				$verify_allow_add_bank_list[] = [ 'name' => 'verifyAllowAddDepositBankDetail', 'params' => [$all_banks, $input_banktype_id] ];
				break;
			case 2:
				$all_banks = $this->playerbankdetails->getWithdrawBankDetail($playerId);
				$verify_allow_add_bank_list[] = [ 'name' => 'verifyAllowAddWithdrawalBankDetail', 'params' => [$all_banks, $input_banktype_id] ];
				break;
			case 3: //create both DP and WD bank at the same time, for future could be used. Not allow to use for now.
				$deposit_banks = $this->playerbankdetails->getDepositBankDetail($playerId);
				$withdrawal_banks = $this->playerbankdetails->getWithdrawBankDetail($playerId);
				$verify_allow_add_bank_list[] = [ 'name' => 'verifyAllowAddDepositBankDetail', 'params' => [$deposit_banks, $input_banktype_id] ];
				$verify_allow_add_bank_list[] = [ 'name' => 'verifyAllowAddWithdrawalBankDetail', 'params' => [$withdrawal_banks, $input_banktype_id] ];
				break;
			default:
				break;
		}
		$verify_function_list = [
			// [ 'name' => 'verifyAllowAddWithdrawalBankDetail', 'params' => [$all_banks, $input_banktype_id] ],
			[ 'name' => 'verifyAvailableBankTypeOnWithdrawal', 'params' => [$input_banktype_id] ],
			[ 'name' => 'execAfterVerifyAvailableBankType', 'params' => [$input_banktype_id, &$banktype, &$payment_type_flag] ],
			[ 'name' => 'verifyAllowModifyAccountName', 'params' => [$payment_type_flag, $oldBankAccountFullName, $newBankAccountFullName] ],
			[ 'name' => 'verifyDuplicateAccountNumber', 'params' => [$playerId, $bank_account_number, $banktype] ],
			[ 'name' => 'verifyBankTypeAddable', 'params' => [$input_banktype_id] ],
			[ 'name' => 'verifyOTPCodeAddBank', 'params' => [$playerId, $otp_code, $otp_source] ],
		];
		$verify_function_list = array_merge($verify_allow_add_bank_list, $verify_function_list);
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============postAddWithdrawal verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============'.__METHOD__.' verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}
		$data = [
			'playerId' => $playerId,
			'bankTypeId' => $input_banktype_id,
			'bankAccountFullName' => $bank_account_full_name,
			'bankAccountNumber' => $bank_account_number,
			'bankAddress' => $bank_address,
			'city' => $city,
			'province' => $province,
			'branch' => $bank_branch,
			// 'isDefault' => $is_default_account,
			'isRemember' => '1',
			// 'dwBank' => Playerbankdetails::WITHDRAWAL_BANK,
			'verified' => '1',
			'status' => '0',
			'phone' => $phone,
		];

		if($is_default_account !== 'no change') {
			$is_default = ($is_default_account == true) ? '1' : '0';
			$data['isDefault'] = $is_default;
		}

		if ($this->utils->getConfig('enable_cpf_number')) {
			$cpf_number = $this->player->getPlayerPixNumberByPlayerId($playerId);
			if(!empty($cpf_number)){
			    $data['pixType'] = 'CPF';
			    $data['pixKey'] = $cpf_number;
			}else{
				$data['pixType']  = '';
				$data['pixKey'] = '';
			}
		}else{
			$data['pixType']  = '';
			$data['pixKey'] = '';
		}
		$add_result = [];
		switch ($bank_account_type) {
			case 1:
				$data['dwBank'] = Playerbankdetails::DEPOSIT_BANK;
				$add_result = $this->processPostAdd($playerId, $data);
				break;
			case 2:
				$data['dwBank'] = Playerbankdetails::WITHDRAWAL_BANK;
				$add_result = $this->processPostAdd($playerId, $data);
				break;
			case 3: //create both DP and WD bank at the same time, for future could be used. Not allow to use for now.
				$data['dwBank'] = Playerbankdetails::DEPOSIT_BANK;
				$add_result = $this->processPostAdd($playerId, $data);
				$data['dwBank'] = Playerbankdetails::WITHDRAWAL_BANK;
				$add_result = $this->processPostAdd($playerId, $data);
				break;
		}
		if($add_result['status'] != 'success') {
			$result['errorCode'] = self::CODE_SERVER_ERROR;
			$result['message'] = $add_result['msg'];
			$result['errorMessage'] = $add_result['msg'];
		}
		else if ($add_result['status'] == 'success') {
			$result['message'] = $add_result['msg'];
            $result['playerBankDetailsId'] = $add_result['playerBankDetailsId'];
			$result['success'] = true;
		}
		return $result;
	}

	protected function processPostAdd($playerId, $data){
		try {
			$bank_list = [];
			$bank_details_id=null;
            $accountType=null;
			switch ($data['dwBank']) {
				case Playerbankdetails::DEPOSIT_BANK:
					$bank_list = $this->playerbankdetails->getPlayerDepositBankList($playerId);
					$bank_details_id = $this->playerbankdetails->addBankDetailsByDeposit($data);
                    $accountType = lang('adjustmenthistory.adjustmenttype.1');

					if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_default_unverified')){
						$data['verified'] = '0';
					}
					break;
				case Playerbankdetails::WITHDRAWAL_BANK:
					$bank_list = $this->playerbankdetails->getPlayerWithdrawalBankList($playerId);
					$bank_details_id = $this->playerbankdetails->addBankDetailsByWithdrawal($data);
                    $accountType = lang('adjustmenthistory.adjustmenttype.2');

					if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_default_unverified')){
						$data['verified'] = '0';
					}
					break;
				default:
					break;
			}

			if(empty($bank_list)) { //bind first bank account or default bank account not exist
				$data['isDefault'] == '1';
			}

			if($data['isDefault'] == '1') {
				$this->playerbankdetails->setPlayerDefaultBank($playerId, $data['dwBank'], $bank_details_id);
			}

            $username = $this->player_model->getUsernameById($playerId);
			//save bank history
			$changes = array(
				'playerBankDetailsId' => $bank_details_id,
				'changes' => lang('Add') . ' ' . lang('lang.bank'),
				'createdOn' => date("Y-m-d H:i:s"),
				'operator' => $username,
			);
			$this->player_model->saveBankChanges($changes);
            $this->player_model->savePlayerUpdateLog($playerId, lang('cashier.103') . ' - ' . $accountType, $username); 
			$message = lang('notify.31');
			return array('status' => 'success', 'msg' => $message, 'playerBankDetailsId' => $bank_details_id);
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$message = lang('notify.30');
			return array('status' => 'failed', 'msg' => $message);
		}
	}

	/**
	* @deprecated No longer used because deactive operator_settings [financial_account_allow_edit]
	*/
	public function postEditWithdrawal($playerId, $player_bank_detail_id, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch, $is_default_account, $province, $city, $phone, $branch_address){
		// $playerId = $this->load->get_var('playerId');
		$bank_detail_id = $player_bank_detail_id;
		$playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id);
		$bank_type = null;
		$payment_type_flag = null;
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		$verify_function_list = [
			[ 'name' => 'verifyAvailableBankTypeOnWithdrawal', 'params' => [$bank_type_id] ],
			[ 'name' => 'execAfterVerifyAvailableBankType', 'params' => [$bank_type_id, &$banktype, &$payment_type_flag] ],
			[ 'name' => 'verifyEmptyPlayerBankDetail', 'params' => [$playerBankDetail] ],
			[ 'name' => 'verifyDuplicateAccountNumber', 'params' => [$playerId, $bank_account_number, $bank_type] ],
			[ 'name' => 'verifyPlayerBankEditable', 'params' => [$bank_detail_id] ],
		];
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============postEditWithdrawal verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============'.__METHOD__.' verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}

		$data = [];
		$data = [
			'bankAccountNumber' => $bank_account_number,
			'bankAccountFullName' => $bank_account_full_name,
			'bankTypeId' => $bank_type_id,
			'branch' => $bank_branch,
			'bankAddress' => $branch_address,
			'city' => $city,
			'province' => $province,
			'phone' => $phone,
			// 'isDefault' => $is_default_account

		];
		if($is_default_account !== 'no change') {
			$is_default = ($is_default_account == true) ? '1' : '0';
			$data['isDefault'] = $is_default;
		}

		if($data['isDefault'] == '1') {
			$this->playerbankdetails->setPlayerDefaultBank($playerId, $playerBankDetail->dwBank, $bank_detail_id);
			unset($data['isDefault']);
		}

		$update_result = $this->processPostEdit($playerId, $bank_detail_id, $data);
		if($update_result['status'] != 'success') {
			$result['errorCode'] = self::CODE_SERVER_ERROR;
			$result['message'] = $update_result['msg'];
			$result['errorMessage'] = $update_result['msg'];
		}
		else if ($update_result['status'] == 'success') {
			$result['message'] = $update_result['msg'];
			$result['success'] = true;
		}
		return $result;
	}

	protected function processPostEdit($playerId, $bank_detail_id, $data){
		try {
			$origbank = $this->player_functions->getBankDetailsById($bank_detail_id);
			$change = $this->checkBankChanges($origbank, $data);
			$changes = array(
				'playerBankDetailsId' => $bank_detail_id,
				'changes' => lang('lang.edit') . ' ' . lang('player.ui07') . ' (' . $change . ')',
				'createdOn' => date("Y-m-d H:i:s"),
				'operator' => $this->authentication->getUsername(),
			);
			$this->player_model->saveBankChanges($changes);

			$data['updatedOn'] = $this->utils->getNowForMysql();
			$this->playerbankdetails->updatePlayerBankDetails($playerId, $bank_detail_id, $data);
			$message = lang('notify.32');
			return array('status' => 'success', 'msg' => $message);
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$message = lang('notify.30');
			return array('status' => 'failed', 'msg' => $message);
		}
	}

	protected function processDelete($playerId, $bank_detail_id){
		$playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id);
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		$verify_function_list = [
			[ 'name' => 'verifyAllowDeleteBankAccountPermission', 'params' => [] ],
			[ 'name' => 'verifyEmptyPlayerBankDetail', 'params' => [$playerBankDetail] ],
			[ 'name' => 'verifyPlayerBankEditable', 'params' => [$bank_detail_id] ],
		];
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============processDelete verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============processDelete verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}
		//save bank history
		$changes = array(
			'playerBankDetailsId' => $bank_detail_id,
			'changes' => lang('Delete') . ' ' . lang('lang.bank'),
			'createdOn' => date("Y-m-d H:i:s"),
			'operator' => $this->authentication->getUsername(),
		);
		$this->player_model->saveBankChanges($changes);
		$this->playerbankdetails->deletePlayerBankInfo($bank_detail_id);
		$result['success'] = true;
		$result['message'] = lang('sys.gd29');
		return $result;
	}

	protected function processSetDefault($playerId, $bank_detail_id) {
		$playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id);
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		$verify_function_list = [
			[ 'name' => 'verifyEmptyPlayerBankDetail', 'params' => [$playerBankDetail] ],
		];
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============processSetDefault verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============processDelete verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}
		$this->playerbankdetails->setPlayerDefaultBank($playerId, $playerBankDetail->dwBank, $bank_detail_id);
		$result['success'] = true;
		$result['message'] = lang('notify.28');
		return $result;
	}

	/**
	 * Checks fields modified on player bank info
	 *
	 * @param array $origbank
	 * @param array $data
	 * @return boolean
	 */
	protected function checkBankChanges($origbank, $data) {
		$array = null;
		if(isset($data['bankTypeId'])){
		    $array .= $origbank['bankTypeId'] != $data['bankTypeId'] ? lang('player.ui35') . ', ' : '';
		}
		if(isset($data['bankAccountNumber'])){
			$array .= $origbank['bankAccountNumber'] != $data['bankAccountNumber'] ? lang('cashier.69') . ', ' : '';
		}
		if(isset($data['bankAccountFullName'])){
			$array .= $origbank['bankAccountFullName'] != $data['bankAccountFullName'] ? lang('cashier.68') . ', ' : '';
		}
		if(isset($data['province'])){
			$array .= $origbank['province'] != $data['province'] ? lang('cashier.70') . ', ' : '';
		}
		if(isset($data['city'])){
			$array .= $origbank['city'] != $data['city'] ? lang('cashier.71') . ', ' : '';
		}
		if(isset($data['branch'])){
			$array .= $origbank['branch'] != $data['branch'] ? lang('cashier.72') . ', ' : '';
		}
		return $modifiedField = empty($array) ? '' : substr($array, 0, -2);
	}

	/**
	 * Update player's communication preference based on changes given
	 *
	 * @return array Update status
	 * @author Cholo Miguel Antonio
	 */
	protected function updatePreference($player_id, $data) {
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		try {
			$data['player_id'] = $player_id;
			$changes = $this->communication_preference_model->getCommunicationPreferenceChanges($data);
			$this->utils->debug_log('==============='. __METHOD__. ' changes', $changes);
			unset($data['player_id']);
			$update_preferences = $this->communication_preference_model->updatePlayerCommunicationPreference($player_id, $data);
			$this->communication_preference_model->saveNewLog($player_id, $changes, $player_id, Communication_preference_model::PLATFORM_PLAYER_CENTER);
			$result['success'] = true;
			$result['message'] = lang('sys.gd25');
			return $result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$result['errorCode'] = self::CODE_SERVER_ERROR;
			$result['message'] = lang('save.failed');
			$result['errorMessage'] = lang('save.failed');
			return $result;
		}
	}

	protected function getPlayerIdByUsernameAndEmailOrPhone($player_username, $contact_type, $contact_info){
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		$verify_function_list = [
			[ 'name' => 'verifyUsernameNotExist', 'params' => [$player_username] ],
		];
		if($contact_type == 'phone') {
			$verify_function_list[] = [ 'name' => 'verifyPhoneNumberBelongToPlayer', 'params' => [null, $player_username, $contact_info] ];
		}
		else if($contact_type == 'email') {
			$verify_function_list[] = [ 'name' => 'verifyEmailAddressBelongToPlayer', 'params' => [null, $player_username, $contact_info] ];
		}
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============getPlayerIdByUsernameAndEmailOrPhone verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============processDelete verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = lang($verify_result['error_message']);
				$result['errorMessage'] = lang($verify_result['error_message']);
				$result['success'] = false;
				return $result;
			}
		}

		try {
			return $this->player_model->getPlayerIdByUsername($player_username);
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$result['errorCode'] = CODE_SERVER_ERROR;
			$result['message'] = lang('save.failed');
			$result['errorMessage'] = lang('save.failed');
			return $result;
		}
	}

	protected function setMailVerifiedByOTPCode($player_id, $otp_code , $email_address) {
		$this->utils->debug_log(__METHOD__, [ 'player_id' => $player_id, 'otp_code' => $otp_code ]);
		$this->load->model(['player_model']);
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		$verify_function_list = [
			// [ 'name' => 'verifyEmailAddressBelongToPlayer', 'params' => [$player_id, $email_address] ],
		];
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============setMailVerifiedByOTPCode verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============'.__METHOD__.' verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}

		$get_player_by_reset_code = $this->player_model->getPlayer(array(
			'playerId' => $player_id,
			// 'email' => $email_address,
			'resetCode' => $otp_code,
			'resetExpire >' => $this->utils->getNowForMysql(),
		));
		if ($get_player_by_reset_code) {
			$already_verified = $this->player_model->isVerifiedInputEmail($player_id, $email_address);
			if(!$already_verified) {
				// -- add player update history
				$success_update = $this->player_model->updatePlayerEmail($player_id, $email_address);
				$success_verify = $this->player_model->verifyEmail($player_id);
				if ($success_update && $success_verify) {
					# sending email
					$this->load->library(['email_manager']);
					$template = $this->email_manager->template('player', 'player_verify_email_success', array('player_id' => $player_id));
					$template_enabled = $template->getIsEnableByTemplateName(true);
					if ($template_enabled['enable']) {
						$email = $this->player->getPlayerById($player_id)['email'];
						$template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $player_id);
					}
					$result['message'] = lang('Verified, please close this modal and refresh the page to check it again.');
					$result['success'] = true;
				} else {
					$result['errorCode'] = self::CODE_SERVER_ERROR;
					$result['message'] = 'Unknown Error during '.__METHOD__;
				}
			}
			else {
				$result['message'] = lang('This email address had been verified before.');
				$result['success'] = true;
			}
		}
		else {
			$result['errorCode'] = self::CODE_INVALID_OTP;
			$result['message'] = lang('Failed to verify, please try it again or contact customer service for assistance');
			$result['errorMessage'] = lang('Failed to verify, please try it again or contact customer service for assistance');
		}
		return $result;
	}

	protected function processSendVerificationEmailToPlayer($playerId, $email_address, $forceBind = false) {
		$this->load->model(['email_verification']);
		$this->load->library(['email_manager']);
		$template_params = array('player_id' => $playerId);
		$template_name = 'player_verify_email';
		$template = null;
		$email = $email_address;
		$resetCode = null;
		if ($this->utils->getConfig('enable_verify_mail_via_otp')) {
			# Obtain the reset code
			$resetCode = $this->generateResetCode($playerId, true);
			$this->utils->debug_log("Reset code: ", $resetCode);
			$template_params['verify_code'] = $resetCode;
		}
		$result = [
			'errorCode' => '',
			'code' => '',
			'message' => '',
            'errorMessage' => '',
			'success' => false
		];
		$verify_function_list = [
			[ 'name' => 'verifySendEmailCoolDownPeriod', 'params' => [$playerId] ],
			[ 'name' => 'verifyEmailTemplateEnabled', 'params' => [$playerId, $email, $template_name, $template_params, &$template] ],
		];

        if(!$forceBind){
            $verify_function_list[] = [ 'name' => 'verifyEmailAddressBelongToPlayer', 'params' => [$playerId, null, $email_address] ];
        }

		$verify_function_list[] = [ 'name' => 'checkPlayerEmailVerified', 'params' => [$playerId, $forceBind] ];

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============processSendVerificationEmailToPlayer verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============'.__METHOD__.' verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['code'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}
		$job_token = $template->sendingEmail($email, Queue_result::CALLER_TYPE_PLAYER, $playerId);
		$record_id = $this->email_verification->recordReport($playerId, $email, $template_name, $resetCode, $job_token);
		$result['message'] = lang('notify.96');
		$result['code'] = self::CODE_OK;
		$result['success'] = true;
		return $result;
	}

	protected function sendVerificationWithOTPSource($playerId, $otp_source, $target, $forceBind=false, $countryPhoneCode=null) {
		$player = $this->player_functions->getPlayerById($playerId);
		if($otp_source == Api_common::OTP_SOURCE_SMS) { //sms=1, email=2, google auth=3
			$phone_number = $target;
			$country_code = '';
			if(!empty($countryPhoneCode)){
				$country_code = $countryPhoneCode;
			}
			if(!empty($player)){
				$phone_number = $player['contactNumber'];
				$country_code = $player['dialing_code'];
			}
			if(empty($phone_number)){
				$result['success'] = false;
				$result['code'] = self::CODE_INVALID_PHONE_NUMBER;
				$result['errorMessage'] = lang("Mobile can't be empty");
				return $result;
			}
			return $this->processSendVerificationSMSToPlayer($playerId, $phone_number, $country_code, $forceBind);
		}
		else if($otp_source == Api_common::OTP_SOURCE_EMAIL) {
			$email_address = $target;
			if(!empty($player)){
				$email_address = $player['email'];
			}
			return $this->processSendVerificationEmailToPlayer($playerId, $email_address, $forceBind);
		}
		else {
			//google auth not imlemeted
			return $this->returnUnimplemented('', true, '*', false, false, 501, "Not Implemented");
		}
	}

	protected function processSendVerificationSMSToPlayer($player_id, $phone_number, $dialing_code=null, $forceBind=false) {
		$this->load->library(array('session', 'sms/sms_sender' ,'voice/voice_sender'));
		$this->load->model(array('sms_verification', 'player_model'));

		$sessionId = $this->session->userdata('session_id');
		$lastSmsTime = $this->session->userdata('last_sms_time');
		if(empty($lastSmsTime)){
			//load from redis
			$lastSmsTime=$this->utils->readRedis($phone_number.'_last_sms_time');
		}
		$smsCooldownTime = $this->config->item('sms_cooldown_time');
		$restrictArea = null;
		$numCount = $this->sms_verification->getTodaySMSCountFor($phone_number);

		$result = [
            'errorCode' => '',
		    'code' => '',
            'message' => '',
            'errorMessage' => '',
            'success' => false,
		];
		$verify_function_list = [
			[ 'name' => 'verifyEnableSMSAPI', 'params' => [] ],
			[ 'name' => 'verifySessionId', 'params' => [$sessionId] ],
			[ 'name' => 'verifySendSMSCoolDownPeriod', 'params' => [$smsCooldownTime, $phone_number, $lastSmsTime] ],
			[ 'name' => 'verifyExceedMaxGenerateCountPerMinute', 'params' => [] ],
			[ 'name' => 'verifyExceedMaxPlayerSMSCountPerDay', 'params' => [$phone_number, $numCount] ],
			[ 'name' => 'checkPlayerPhoneNumberVerified', 'params' => [$player_id, $forceBind]],
			[ 'name' => 'verifyDuplicatePhoneNumber', 'params' => [$player_id, $phone_number, $forceBind] ],
			[ 'name' => 'verifyExceedRestrictAreaSendCount', 'params' => [$player_id, $restrictArea] ],
		];
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============postEditWithdrawal verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['code'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $result;
			}
		}

		if($restrictArea == NULL) {
			$restrictArea = sms_verification::USAGE_DEFAULT;
		}
		$code = $this->sms_verification->getVerificationCode($player_id, $sessionId, $phone_number, $restrictArea);

		$use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
		if ($use_new_sms_api_setting) {
			#restrictArea = action type
			list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($player_id, $phone_number, $restrictArea, $sessionId);
			$this->utils->debug_log(__METHOD__, 'use new sms api',$useSmsApi, $sms_setting_msg, $restrictArea);

			if (empty($useSmsApi)) {
				$result['code'] = self::CODE_API_IS_UNAVAILABLE;
				$result['errorMessage'] = $sms_setting_msg;
				return $result;
			}
		}else{
			$useSmsApi = $this->sms_sender->getSmsApiName();
		}

		$msg = $this->utils->createSmsContent($code, $useSmsApi);
		$mobileNum = !empty($dialing_code)? $dialing_code.'|'.$phone_number : $phone_number;
		if( !empty($this->config->item('switch_voice_under_sms_limit_num_per_day'))){
			if( $numCount >= $this->config->item('switch_voice_under_sms_limit_num_per_day')){
				$useVoiceApi = $this->voice_sender->getvoiceApiName();
				if($useVoiceApi != 'disable'){
					if ($this->voice_sender->send($mobileNum, $code, $useVoiceApi)) {
						$this->session->set_userdata('last_sms_time', time());
						$this->utils->writeRedis($phone_number.'_last_sms_time', time());
						$result['success'] = true;
						$result['code'] = self::CODE_OK;
					} else {
						$result['code'] = self::CODE_EXTERNAL_API_ERROR;
						$result['errorMessage'] = $this->voice_sender->getLastError();
					}
					return $result;
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
			$this->utils->writeRedis($phone_number.'_last_sms_time', time());
			$result['success'] = true;
			$result['code'] = self::CODE_OK;
		} else {
			if ($this->sms_sender->send($mobileNum, $msg, $useSmsApi)) {
				$this->session->set_userdata('last_sms_time', time());
				$this->utils->writeRedis($phone_number.'_last_sms_time', time());
				$result['success'] = true;
				$result['code'] = self::CODE_OK;
			} else {
				$result['code'] = self::CODE_EXTERNAL_SMS_API_ERROR;
				$result['errorMessage'] = $this->sms_sender->getLastError();
			}
		}
		return $result;
	}

	/**
	 * update phone verification flag
	 *
	 * @param  string $contact_number
	 * @param  string $sms_verification_code
	 * @return json
	 */
	public function setPhoneNumberVerifiedByOTPCode($playerId, $contact_number, $sms_verification_code, $restrict_area='sms_api_security_setting') {
		$result = [
			'code' => ''
		];
// $this->utils->debug_log('OGP-19808.2180.$update_sms_verification');
		$this->load->library('session');
		$this->load->model(['sms_verification','player_model']);
		// $session_id = $this->session->userdata('session_id');
		$session_id = Sms_verification::SESSION_ID_DEFAULT;
		$success = false;
		$message=lang('Verify SMS Code Failed');
		// $error_code = '';
		$player = $this->player_functions->getPlayerById($playerId);
		if($player['verified_phone'] && $player['contactNumber'] == $contact_number) {
			// $result['success'] = true;
			// $result['message'] = lang('This phone number had already been verified');
			$result['code'] = self::CODE_OK;
			$result['successMessage'] = lang('This phone number had already been verified');
			return $result;
		}
		if(!empty($playerId) && !empty($session_id) && !empty($contact_number) && !empty($sms_verification_code)){
			$success = !isset($sms_verification_code) || $this->sms_verification->validateVerificationCode($playerId, $session_id, $contact_number, $sms_verification_code, $restrict_area);
			if(!$success) {
				$this->utils->debug_log('========== validate sms_verification_code from back office =====', $success);
				// validte verification code from back office
				$success = $this->sms_verification->validateVerificationCode($playerId, null, $contact_number, $sms_verification_code);
				$result['errorMessage']=lang('Verify SMS Code Failed');
				$result['code'] = self::CODE_INVALID_OTP;
			}
			// $this->utils->debug_log('OGP-19808.2180.$success:', $success);
			$this->utils->debug_log('========== sms_verification_code result =====', $success);
			if($success){
				$success=$this->player_model->updateAndVerifyContactNumber($playerId, $contact_number);
				// $this->utils->debug_log('OGP-19808.2184.$success:', $success);
				if(!$success){
					$result['errorMessage']=lang('Verify SMS Code Failed');
					$result['code'] = self::CODE_INVALID_OTP;
				}
			}else{
				$result['errorMessage']=lang('Verify SMS Code Failed');
				$result['code'] = self::CODE_INVALID_OTP;
			}
			if($success){
				$username= $this->authentication->getUsername();
				$this->player_functions->savePlayerUpdateLog($playerId, lang('Phone verified by player: ') . ' ' . $username, $username);
				$result['successMessage']=lang('Verify SMS Code Successfully');
				$result['code'] = self::CODE_OK;
				$this->syncPlayerCurrentToMDBWithLock($playerId, $username, false);
			}
		}
		
		return $result;
	}

	public function setNewPasswordByOTPCode($mode, $set_type, $player_id=null, $username='', $otp_source, $otp_code, $new_password, $original_password='') {
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];

		$this->load->model(['sms_verification']);
		$verify_function_list = [
			[ 'name' => 'verifyPasswordLimit', 'params' => [$new_password] ],
		];
		if($mode == 'forgot') {
			if ($set_type == 'withdrawal') {
				$verify_function_list[] = [ 'name' => 'verifyEnableWithdrawalPassword', 'params' => [] ];
			}else{
				$verify_function_list[] = [ 'name' => 'verifyUsernameNotExist', 'params' => [$username] ];
                $player_id = $this->player_model->getPlayerIdByUsername($username);
			}
		}
		else if($mode == 'change') {
			$verify_function_list[] = [ 'name' => 'verifyPlayerPassword', 'params' => [$player_id, $original_password] ];
		}
		else {
			$result['message'] = 'Unexpected mode!';
			return $result;
		}
		if($otp_source == playerapi::OTP_SOURCE_EMAIL){
			$verify_function_list[] = [ 'name' => 'checkPlayerEmailVerified', 'params' => [$player_id] ];
		}
		if($otp_source == playerapi::OTP_SOURCE_SMS){
			$verify_function_list[] = [ 'name' => 'checkPlayerPhoneNumberVerified', 'params' => [$player_id] ];
		}
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============setNewPasswordByOTPCode verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============'.__METHOD__.' verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				$result['success'] = false;
				return $result;
			}
		}

		$process_result = null;
		if($mode == 'forgot') {
			$player_id = !empty($player_id) ? $player_id : $this->player_model->getPlayerIdByUsername($username);
			$player_contact = $this->player->getPlayerContactInfo($player_id);
			$process_result = $this->checkOTPCodeWithSource($otp_source, $player_id, $player_contact['contactNumber'], $otp_code);
			// $this->utils->debug_log('============'.__METHOD__.' process_result of checkOTPCodeWithSource', $process_result);
			if($process_result['success']) {
				if($set_type == 'login'){
					$process_result = $this->updatePasswordAndResetExpire($player_id, $new_password);
				// $this->utils->debug_log('============'.__METHOD__.' process_result of updatePasswordAndResetExpire', $process_result);
				}else if($set_type == 'withdrawal') {
					$process_result = $this->changePlayerWithdrawalPassword($player_id, $new_password);
				}
			}
		}
		else if($mode == 'change') {
			$player_contact = $this->player->getPlayerContactInfo($player_id);
			$process_result = $this->checkOTPCodeWithSource($otp_source, $player_id, $player_contact['contactNumber'], $otp_code);
			// $this->utils->debug_log('============'.__METHOD__.' process_result of checkOTPCodeWithSource', $process_result);
			if($process_result['success']) {
				if($set_type == 'login') {
					$process_result = $this->changePlayerPassword($player_id, $new_password);
					// $this->utils->debug_log('============'.__METHOD__.' process_result of changePlayerPassword', $process_result);
				}
				else if($set_type == 'withdrawal') {
					$process_result = $this->changePlayerWithdrawalPassword($player_id, $new_password);
				}
			}
		}

		$message = $process_result['message'];
		$result['message'] = $message;
		$result['errorMessage'] = $message;
		if($process_result['success']) {
			$result['success'] = true;
		}
		else {
			$result['errorCode'] = $process_result['errorCode'];
			$result['success'] = false;
		}
		return $result;
	}

	private function checkOTPCodeWithSource($otp_source, $player_id, $contact_number, $otp_code) {
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		if($otp_source == playerapi::OTP_SOURCE_SMS) { //sms=1, email=2, google auth=3
			$this->load->model(['sms_verification']);
			$use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
			$usage = !empty($use_new_sms_api_setting) ? sms_verification::USAGE_SMSAPI_FORGOTPASSWORD : sms_verification::USAGE_DEFAULT; //sms_verification::USAGE_NEW_PLAYERAPI_PASSWORD_RECOVERY;

			// if(empty($player_id)) {
			// 	$usage = sms_verification::USAGE_DEFAULT;
			// }
			$session_id = Sms_verification::SESSION_ID_DEFAULT;
			if ($this->sms_verification->validateVerificationCode($player_id, $session_id, $contact_number, $otp_code, $usage)) {
				$result['success'] = true;
			}
			else {
				$result['errorCode'] = self::CODE_INVALID_OTP;
				$result['message'] = lang('Failed to verify, please try it again or contact customer service for assistance');
				$result['errorMessage'] = lang('Failed to verify, please try it again or contact customer service for assistance');
			}
		}
		else if ($otp_source == playerapi::OTP_SOURCE_EMAIL){
			$get_player_by_reset_code = $this->player_model->getPlayer(array(
				'playerId' => $player_id,
				'resetCode' => $otp_code,
				'resetExpire >' => $this->utils->getNowForMysql(),
			));

			if ($get_player_by_reset_code) {
				if ($this->checkResetCodeResetExpire($otp_code, $player_id)) {
					$result['success'] = true;
				}else{
					$result['errorCode'] = self::CODE_INVALID_OTP;
					$result['message'] = lang('Failed to verify, please check for any blanks or contact customer service for assistance');
					$result['errorMessage'] = lang('Failed to verify, please check for any blanks or contact customer service for assistance');
				}
			}
			else {
				$result['errorCode'] = self::CODE_INVALID_OTP;
				$result['message'] = lang('Failed to verify, please try it again or contact customer service for assistance');
				$result['errorMessage'] = lang('Failed to verify, please try it again or contact customer service for assistance');
			}
		}
		return $result;
	}

	private function changePlayerPassword($player_id, $password) {
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];

		try {
			$player = $this->player_model->getPlayerArrayById($player_id);
			if ($this->utils->getConfig('restrict_player_login_pwd_cannot_same_withdrawal_pwd')) {
				$withdrawal_pwd = $player['withdraw_password'];
				$this->utils->debug_log(__METHOD__, 'withdrawal_pwd', $withdrawal_pwd);
				if ($withdrawal_pwd == $password) {
					$message = lang('notify.restrict_player_login_pwd_cannot_same_withdrawal_pwd');
					$result['errorCode'] = self::CODE_INVALID_PASSWORD;
					$result['message'] = $message;
					$result['errorMessage'] = $message;
					return $result;
				}
			}

			// save change password  in player_password_history
			$this->player_model->insertPasswordHistory($player_id, Player_model::CHANGE_PASSWORD, $this->utils->encodePassword($password));
			$data = array(
				'password' => $password,
				'is_phone_registered' => PHONE_REGISTERED_YET_AND_CHANGE_PASSWORD
			);
			$this->player_functions->resetPassword($player_id, $data);

			//sync
			$username = $player['username'];
			$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

			//save changes to playerupdatehistory
			$this->player_model->savePlayerUpdateLog($player_id, lang('system.word8'), $username);

			//$message = "Your Password has successfully changed";
			$message = lang('notify.27');
			$this->session->unset_userdata('player_can_directly_set_passwd');
			$result['message'] = $message;
			$result['success'] = true;
			return $result;

		} catch (Exception $ex) {
			$result['message'] = 'Unknown Error during '.__METHOD__;
			$result['errorCode'] = self::CODE_SERVER_ERROR;
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			return $result;
		}
	}

	private function changePlayerWithdrawalPassword($player_id, $new_password) {
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];

		try {
			$player = $this->player_model->getPlayerArrayById($player_id);

			if ($this->utils->getConfig('restrict_player_login_pwd_cannot_same_withdrawal_pwd')) {
				$withdrawal_pwd = $player['withdraw_password'];
				$this->utils->debug_log(__METHOD__, 'withdrawal_pwd', $withdrawal_pwd);
				if ($withdrawal_pwd == $new_password) {
					$message = lang('notify.restrict_player_login_pwd_cannot_same_withdrawal_pwd');
					$result['errorCode'] = self::CODE_INVALID_PASSWORD;
					$result['message'] = $message;
					$result['errorMessage'] = $message;
					return $result;
				}
			}
			$data['withdraw_password'] = $new_password;
			$this->player_model->resetPassword($player_id, $data);

			#sending email
			// $this->load->library(['email_manager']);
			// $template = $this->email_manager->template('player', 'player_change_withdrawal_password_successfully', array('player_id' => $player_id, 'new_withdrawal_password' => $new_password));
			// $template_enabled = $template->getIsEnableByTemplateName(true);
			// if($template_enabled['enable']){
			// 	$template->sendingEmail($player['email'], Queue_result::CALLER_TYPE_PLAYER, $player_id);
			// }

			//save changes to playerupdatehistory
			$this->player_model->savePlayerUpdateLog($player_id, lang('Withdraw Reset Password'), $this->authentication->getUsername());

			//sync
			$username = $this->player_model->getUsernameById($player_id);
			//save changes to playerupdatehistory
			$this->player_model->savePlayerUpdateLog($player_id, lang('Withdraw Reset Password'), $username);
			$this->syncPlayerCurrentToMDBWithLock($player_id, $username, false);

			// $result['message'] = $message;
			$result['success'] = true;
			return $result;

		} catch (Exception $ex) {
			$result['message'] = 'Unknown Error during '.__METHOD__;
			$result['errorCode'] = self::CODE_SERVER_ERROR;
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			return $result;
		}
	}

	public function sendMsgToRemindChangePassword($username) {
		$this->load->model(array('player_model'));
		$player_id = $this->player_model->getPlayerIdByUsername($username);

		$send_msg_to_remind_change_password = $this->utils->getConfig('send_msg_to_remind_change_password');
		$period  = isset($send_msg_to_remind_change_password['period']) ? $send_msg_to_remind_change_password['period']  : '';
		$subject = isset($send_msg_to_remind_change_password['subject'])? $send_msg_to_remind_change_password['subject'] : null;
		$message = isset($send_msg_to_remind_change_password['message'])? $send_msg_to_remind_change_password['message'] : null;

		$lastResetPassword = $this->player_model->getLastResetPassword($player_id);
		$is_message_notify = isset($lastResetPassword['is_message_notify']) ? $lastResetPassword['is_message_notify'] : 0;
		$updated_at = isset($lastResetPassword['updated_at']) ? $lastResetPassword['updated_at'] : '';

		if (!empty($updated_at)){
			$currentDate = new DateTime();
			$lastUpdateDate = new DateTime($updated_at);
			$interval = $currentDate->diff($lastUpdateDate);
			$minutesDifference = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

			if ($minutesDifference >= $period && $is_message_notify == 0) {
				$userId  = 1;
				$messageId = $this->utils->adminSendMsg($userId, $player_id, $username, $subject, $message);

				if (!empty($messageId)) {
					$is_message_notify = 1;
					$id = isset($lastResetPassword['id']) ? $lastResetPassword['id'] : null;
					$this->player_model->updatePlayerPasswordHistory($id, $is_message_notify, $messageId);
					$this->utils->debug_log(__METHOD__, 'updatePlayerPasswordHistory', ['messageId' => $messageId, 'lastResetPassword' => $lastResetPassword]);
				}
			}
			$this->utils->debug_log(__METHOD__, ['is_message_notify' => $is_message_notify, 'currentDate' => $currentDate, 'lastUpdateDate' => $lastUpdateDate, 'minutesDifference' => $minutesDifference]);
		}
	}

	public function sendMsgToRemindReKyc($username) {
		$this->load->model(array('player_model','kyc_status_model'));
		$player_id = $this->player_model->getPlayerIdByUsername($username);

		$send_msg_to_remind_change_password = $this->utils->getConfig('send_msg_to_remind_re_kyc');
		$period  = isset($send_msg_to_remind_change_password['period']) ? $send_msg_to_remind_change_password['period']  : '';
		$subject = isset($send_msg_to_remind_change_password['subject'])? $send_msg_to_remind_change_password['subject'] : '';
		$message = isset($send_msg_to_remind_change_password['message'])? $send_msg_to_remind_change_password['message'] : '';
		$link    = isset($send_msg_to_remind_change_password['link'])? $send_msg_to_remind_change_password['link'] : '';

		$verification_data = $this->kyc_status_model->get_verification_info($player_id);
		$photo_id_data = isset($verification_data['photo_id']) ? $verification_data['photo_id'] : '';

		if (!empty($photo_id_data) && array_key_exists(BaseModel::Remark_Verified, $photo_id_data)) {
			$verified_data = isset($photo_id_data['verified']) ? $photo_id_data['verified'] : '';
			$set_verified_date = isset($verified_data['set_verified_date']) ? $verified_data['set_verified_date'] : '';
			$is_message_notify = isset($verified_data['is_message_notify']) ? $verified_data['is_message_notify'] : 0;

			if(!empty($set_verified_date)) {
				$currentDate = new DateTime();
				$set_verified_date = new DateTime($set_verified_date);
				$interval = $currentDate->diff($set_verified_date);
				$minutesDifference = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

				if ($minutesDifference >= $period) {
					$userId  = 1;
					$messageId = $this->utils->adminSendMsg($userId, $player_id, $username, $subject, $message.' '.$link);

					if (!empty($messageId)) {
						$is_message_notify = 1;
						$verified_data['is_message_notify'] = $is_message_notify;
						$verified_data['messageId'] = $messageId;

						$data = [
							BaseModel::Verification_Photo_ID => [
								BaseModel::Remark_No_Attach => $verified_data,
							],
						];

						$this->kyc_status_model->update_verification_data($player_id, $data);
						$this->utils->debug_log(__METHOD__, 'update_verification_data', ['messageId' => $messageId, 'data' => $data]);
					}
				}
				$this->utils->debug_log(__METHOD__, ['currentDate' => $currentDate, 'set_verified_date' => $set_verified_date, 'minutesDifference' => $minutesDifference]);
			}
		}
	}

	public function handleValidateCaptcha($playerId = null) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		if(!$this->utils->getConfig('temp_disable_sbe_register_setting')){
			$request_body = $this->playerapi_lib->getRequestPramas();
			$is_thirdparty_captcha_enabled = (!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label']));
			if($is_thirdparty_captcha_enabled){
				$validate_fields = [
					['name' => 'captchaToken', 'type' => 'string', 'required' => true, 'length' => 0],
				];
			}else{
				$validate_fields = [
					['name' => 'captchaKey', 'type' => 'string', 'required' => true, 'length' => 0],
					['name' => 'captchaCode', 'type' => 'string', 'required' => true, 'length' => 0]
				];
			}
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			if(!$is_validate_basic_passed['validate_flag']) {
				$verify_result['passed'] = false;
				$verify_result['code'] = Playerapi::CODE_INVALID_PARAMETER;
				$verify_result['error_message']= $is_validate_basic_passed['validate_msg'];
				return $verify_result;
			}

			if($is_thirdparty_captcha_enabled){
				$captcha_token = $request_body['captchaToken'];
				$check_result = $this->checkThirdPartyCaptchaToken($playerId, $captcha_token);
			}else{
				$captcha_code = $request_body['captchaCode'];
				$captcha_key = $request_body['captchaKey'];
				$check_result = $this->checkIsCaptchaValid($playerId, $captcha_code, $captcha_key);
			}

			if(!$check_result['success']){
				$verify_result['passed'] = false;
				$verify_result['code'] = $check_result['errorCode'];
				$verify_result['error_message']= $check_result['errorMessage'];
				return $verify_result;
			}
		}
		return $verify_result;
	}
	public function checkThirdPartyCaptchaToken($playerId, $captcha_token){
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];

		if(!empty($this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'])){
			$captcha_label = $this->utils->getConfig('enabled_captcha_of_3rdparty')['3rdparty_label'];
			$check_hcaptcha_code = $this->utils->checkThirdPartyCapchaCode($captcha_label, $captcha_token);
			if(!$check_hcaptcha_code){
				$message = lang('Captcha code invalid');
				$result['message'] = $message;
				$result['errorMessage'] = $message;
				$result['errorCode'] = self::CODE_INVALID_CAPTCHA;
			}else{
				$message = lang('info.captcha.success');
				$result['message'] = $message;
				$result['success'] = true;
			}
			return $result;
		}
	}
	public function checkIsCaptchaValid($playerId, $captcha_code, $captcha_token) {
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];

		$this->load->library('captcha/securimage');
		$jsonArray = null;
		// $player = $this->player_functions->getPlayerById($playerId);
		// if($player['contactNumber'] != $contact_number) { //ignore validte country_code cuz there are too many dirty data without country_code
		// 	$message = lang('Phone number not found');
		// 	$result['errorCode'] = self::CODE_PLAYER_PHONE_OPERATION_FAILED;
		// 	$result['message'] = $message;
		// 	return $result;
		// }
		if( ! empty($captcha_token) ){
			$captcha_cache_token = sprintf('%s-%s', Securimage::cache_prefix, $captcha_token);

			$jsonArray = $this->utils->getJsonFromCache($captcha_cache_token);
			// delete cache, only use once
			$this->utils->deleteCache($captcha_cache_token);
		}
		$this->utils->debug_log('===========================checkIsCaptchaValid process captcha_code', $captcha_code, 'captcha_token', $captcha_token, 'jsonArray', $jsonArray);
		if( empty($captcha_code) || empty($captcha_token) || empty($jsonArray)) {
			$message = lang('Captcha invalid or empty');
			$result['message'] = $message;
			$result['errorMessage'] = $message;
			$result['errorCode'] = self::CODE_INVALID_CAPTCHA;
		} else if(  strtolower($jsonArray['code']) != strtolower($captcha_code) ) {
			$message = lang('Captcha code invalid');
			$result['message'] = $message;
			$result['errorMessage'] = $message;
			$result['errorCode'] = self::CODE_INVALID_CAPTCHA;
		} else if(  strtolower($jsonArray['code']) == strtolower($captcha_code) ) {
			$message = lang('info.captcha.success');
			$result['message'] = $message;
			$result['success'] = true;
		}
		return $result;
	}

	private function addProfileDefaultAvatar($playerId, $avatarUrl){
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			$this->load->model(array('player_attached_proof_file_model'));

			$input = array(
				'player_id' => $playerId,
				'tag' => player_attached_proof_file_model::PROFILE_PICTURE,
			);

			$data = [
				'input' => $input,
				'image' => 'default_avatar_'.$avatarUrl
			];

			$upload_resp = $this->player_attached_proof_file_model->add_profile_default_avatar($data);
			$this->comapi_log("=====================".__METHOD__, 'upload_resp', $upload_resp, $data);

			if(!empty($upload_resp)){
				if($upload_resp['status'] == "success"){
					$verify_result['error_code'] = self::CODE_OK;
					$verify_result['error_message'] = lang('Attachment uploaded successfully');
				} else {
					// $result['data']['message'] = lang("Error uploading file").': '.$upload_resp['msg'];
					$message = lang("Error uploading file").': '.$upload_resp['msg'];
					$verify_result['error_code'] = self::CODE_AVATAR_UPLOAD_FAILED;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
				}
			}

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyFieldFormat($profileData){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$names_regex = $this->utils->getConfig('new_api_verify_names_invalid_chars');
			$default_regex = '/^[0-9]+$/';

			if(!empty($profileData['firstName'])){
				$position = strcspn($profileData['firstName'], $names_regex);
				if ($position !== strlen($profileData['firstName'])) {
					$message = sprintf(lang('formvalidation.regex_match'), lang('reg.fields.firstName'));
					$verify_result['error_code'] = self::CODE_INVALID_FORMAT;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
					return $verify_result;
				}

				$validate_res = $this->playerapi_lib->handleValidateField($profileData['firstName'], 'first_name');
				if(!$validate_res['passed']){
					$verify_result['error_code'] = self::CODE_INVALID_FORMAT;
					$verify_result['error_message'] = $validate_res['error_message'];
					$verify_result['passed'] = false;
					return $verify_result;
				}
			}

			if(!empty($profileData['lastName'])){
				$position = strcspn($profileData['lastName'], $names_regex);
				if ($position !== strlen($profileData['lastName'])) {
					$message = sprintf(lang('formvalidation.regex_match'), lang('reg.fields.lastName'));
					$verify_result['error_code'] = self::CODE_INVALID_FORMAT;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
					return $verify_result;
				}

				$validate_res = $this->playerapi_lib->handleValidateField($profileData['lastName'], 'last_name');
				if(!$validate_res['passed']){
					$verify_result['error_code'] = self::CODE_INVALID_FORMAT;
					$verify_result['error_message'] = $validate_res['error_message'];
					$verify_result['passed'] = false;
					return $verify_result;
				}
			}

			if(!empty($profileData['pix_number'])){
				if (!preg_match($default_regex, $profileData['pix_number'])) {
					$message = sprintf(lang('formvalidation.regex_match'), lang('financial_account.CPF_number'));
					$verify_result['error_code'] = self::CODE_INVALID_FORMAT;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
					return $verify_result;
				}

				$validate_res = $this->playerapi_lib->handleValidateField($profileData['pix_number'], 'cpf_number');
				if(!$validate_res['passed']){
					$verify_result['error_code'] = self::CODE_INVALID_FORMAT;
					$verify_result['error_message'] = $validate_res['error_message'];
					$verify_result['passed'] = false;
					return $verify_result;
				}
			}

			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function generateEditPlayerVerifyMethods($playerId, $profileData){
		$verify_function_list = [];
		$verify_function_list = [
			[ 'name' => 'verifyEditableInAccountSetting', 'params' => [$playerId, $profileData] ],
			[ 'name' => 'verifyFieldOnlyEditedOnce',      'params' => [$playerId, $profileData] ],
			[ 'name' => 'verifyFieldFormat',              'params' => [$profileData] ],
		];

		if(isset($profileData['contactNumber'])){
			$verify_function_list[] = [ 'name' => 'verifyDuplicatePhoneNumber', 'params' => [$playerId, $profileData['contactNumber']]];
			$verify_function_list[] = [ 'name' => 'checkVerifiedPhoneNumber', 'params' => [$playerId, $profileData['contactNumber']]];
		}

		if(isset($profileData['pix_number'])){
			$verify_function_list[] = [ 'name' => 'verifyDuplicateCPFNumber', 'params' => [$playerId, $profileData['pix_number']]];
			$verify_function_list[] = [ 'name' => 'checkVerifiedCpfNumber', 'params' => [$playerId, $profileData['pix_number']]];
		}

		if(isset($profileData['email'])){
			$verify_function_list[] = [ 'name' => 'verifyDuplicateEmail', 'params' => [$playerId, $profileData['email']]];
			$verify_function_list[] = [ 'name' => 'checkVerifiedEmail', 'params' => [$playerId, $profileData['email']]];
		}

		if (!empty($profileData['avatarUrl'])) {
			$verify_function_list[] = [ 'name' => 'verifyEnableUploadAvatarFile', 'params' => []];
			$verify_function_list[] = [ 'name' => 'addProfileDefaultAvatar', 'params' => [$playerId, $profileData['avatarUrl']]];
		}

		if(!empty($profileData['birthdate'])){
			$verify_function_list[] = [ 'name' => 'verifyAgeRegistration', 'params' => [$profileData['birthdate'], true]];
		}

		return $verify_function_list;
	}

	public function postEditPlayerAccountProfile($playerId, $profileData) {
		$this->load->model(array('registration_setting', 'player_model'));
		$player 		  = $this->player_functions->getPlayerById($playerId);
		$today 		 	  = date("Y-m-d H:i:s");
		$playerData 	  = ['updatedOn' => $today];
		$playerEmail 	  = array();
		$playerDetailData = array();
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];

		$verify_function_list = $this->generateEditPlayerVerifyMethods($playerId, $profileData);
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============processDelete verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['errorMessage'] = $verify_result['error_message'];
				throw new APIException($result['errorMessage'], $result['errorCode']);
			}
		}

		$this->player_functions->editPlayer($playerData, $playerId);

		if(empty($profileData['language'])) {
			if(!empty($player['language'])){
				$language = $player['language'];
			}else{
				$language = $this->utils->getConfig('default_player_language');
			}
		}else{
			$language = $profileData['language'];
		}

		if($this->utils->getConfig('enable_default_country_when_post_dialing_code')){
			if(empty($profileData['citizenship']) && !empty($profileData['dialing_code'])){
				$profileData['citizenship'] = $this->getCountryCodeByPhoneCode($profileData['dialing_code']);
			}
		}

		foreach($profileData as $fieldName => $value) {
            switch ($fieldName) {
                case 'email':
                    $playerEmail['email'] = $value;
                    $this->player_functions->editPlayerEmail($playerEmail, $playerId);
                    break;
                default:
                    if(!empty($value)){
                        $playerDetailData[$fieldName] = $value;
                    }
                    break;
            }
        }

		//set language
		$this->language_function->setCurrentLanguage($this->language_function->langStrToInt($language));
		$username = $this->player_model->getUsernameById($playerId);
		unset($playerDetailData['avatarUrl'], $playerDetailData['sms_verification_code']);
		//save changes to playerupdatehistory
		$modifiedFields = $this->player_library->checkModifiedFields($playerId, $playerDetailData);
		$this->player_library->savePlayerUpdateLog($playerId, lang('lang.edit') . ' ' . lang('lang.playerinfo') . ' (' . $modifiedFields . ')', $username);

		if(!empty($playerDetailData)) {
			$this->CI->utils->debug_log('===========================postEditPlayer Data', $playerDetailData);
			$this->player_functions->editPlayerDetails($playerDetailData, $playerId);
		}

		//sync
		$this->syncPlayerCurrentToMDBWithLock($playerId, $username, false);

		if($this->utils->getConfig('enable_fast_track_integration')) {
			$this->load->library('fast_track');
			$this->fast_track->updateUser($playerId);
		}

		$result['success'] = true;
		$result['message'] = lang('notify.24');
		return $result;
	}

	protected function isRegistrationFieldRequired($fieldName){
		// $this->load->model(['registration_setting']);
		// return $this->registration_setting->isRegistrationFieldRequired($fieldName);
		$enable_form_required_under_registration = $this->utils->getConfig('enable_form_required_under_registration');
		return in_array($fieldName, $enable_form_required_under_registration);
	}

	private function verifyRegistrationSetting($post_data)
	{
		try {
			$this->load->model(['registration_setting']);
			$verifyResult = ['passed' => true, 'error_message' => ''];
			$notAllowedField = '';
			if(!$this->utils->getConfig('temp_disable_sbe_register_setting')){
				$registrationFields = $this->registration_setting->getRegistrationFieldsByAlias();
				$enableFormValidationUnderRegistration = $this->utils->getConfig('enable_form_validation_under_registration');
				$playerPreferenceList = $this->registration_setting->getPlayerPreferenceAlias();
				foreach ($registrationFields as $alias => $settings) {
					$skipValidation = (!in_array($alias, $enableFormValidationUnderRegistration));
					if($skipValidation){
						continue;
					}

                    $isRequired = ($settings['required'] == REGISTRATION_SETTING::REQUIRED);
                    $isVisabled = ($settings['visible'] == REGISTRATION_SETTING::VISIBLE);
                    $isPostEmpty = (!isset($post_data[$alias]) || $post_data[$alias] === '');
    
					if($isPostEmpty && $isRequired){
						$notAllowedField = $this->playerapi_lib->matchOutputRegistrationNames($alias);
						$message = sprintf('[%s] %s', $notAllowedField, lang('is_required'));
						$verifyResult['error_code'] = self::CODE_INVALID_PARAMETER;
						$verifyResult['error_message'] = $message;
						$verifyResult['passed'] = false;
						return $verifyResult;
					}

					if(!$isPostEmpty && !$isVisabled){
						$notAllowedField =  $this->playerapi_lib->matchOutputRegistrationNames($alias);
						$message = sprintf(lang('reg.not_allowed_edit_for_acc_setting'), $notAllowedField);
						$verifyResult['error_code'] = self::CODE_INVALID_PARAMETER;
						$verifyResult['error_message'] = $message;
						$verifyResult['passed'] = false;
						return $verifyResult;
					}

					if( !$isPostEmpty && 
                        in_array($alias, $playerPreferenceList)
                        && $registrationFields['player_preference']['visible'] != Registration_setting::VISIBLE)
                    {
						$notAllowedField =  $this->playerapi_lib->matchOutputRegistrationNames($alias);
						$message = sprintf(lang('reg.not_allowed_edit_for_acc_setting'), $notAllowedField);
						$verifyResult['error_code'] = self::CODE_INVALID_PARAMETER;
						$verifyResult['error_message'] = $message;
						$verifyResult['passed'] = false;
						return $verifyResult;
					}

					if(!empty($settings['options'])){
						$validateContent['content'] = [];
						$validateContent['allowed'] = false;
						$options = json_decode($settings['options'], true);
						foreach($options as $option){
							$validateContent['content'][] = $option['value'];
							if($option['value'] == $post_data[$alias]){
								$validateContent['allowed'] = true;
								$subFields = isset($option['subFields'])? $option['subFields'] : [];
								foreach($subFields as $subField){
									if(empty($post_data[$subField['name']]) && isset($subField['require']) && $subField['require'] ){
										$notAllowedField = $this->playerapi_lib->matchOutputRegistrationNames($subField['name']);
										$message = sprintf('[%s] %s', $notAllowedField, lang('is_required'));
										$verifyResult['error_code'] = self::CODE_INVALID_PARAMETER;
										$verifyResult['error_message'] = $message;
										$verifyResult['passed'] = false;
										return $verifyResult;
									}
								}
							}
						}

						if(!$validateContent['allowed'] && !empty($validateContent['content']) && !$isPostEmpty){
							$notAllowedField =  $this->playerapi_lib->matchOutputRegistrationNames($alias);
							$message = '{' . $notAllowedField . '} value should be ' . implode(" or ", $validateContent['content']) . '. ';
							$verifyResult['error_code'] = self::CODE_INVALID_PARAMETER;
							$verifyResult['error_message'] = $message;
							$verifyResult['passed'] = false;
							return $verifyResult;
						}
					}
				}
			}
			return $verifyResult;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verifyResult = $this->playerapi_lib->setVerifyResultErrorMsg($verifyResult, 'Unknown Error during '.__METHOD__);
			return $verifyResult;
		}
	}

	public function verifyAgeRegistration($birthday, $terms){
		try {
			$verify_result = ['passed' => true, 'error_message' => ''];
			if(!$this->utils->getConfig('temp_disable_sbe_register_setting')){
				if($terms){
					$valid = $this->check_registration_age($birthday);
					if(!$valid){
						$limitAge = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
						$message = sprintf(lang('mod.mustbeAtLeastLimitAge'),$limitAge);
						$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
						$verify_result['error_message'] = $message;
						$verify_result['passed'] = false;
					}
				}else{
					$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
					$verify_result['error_message'] = lang('mod.termsAndConditions');
					$verify_result['passed'] = false;
				}
			}
			return $verify_result;
		}catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	public function check_registration_age($birthday) {
		$limitAge = $this->operatorglobalsettings->getSettingJson('registration_age_limit');
		$age = DateTime::createFromFormat('Y-m-d', $birthday)->diff(new DateTime('now'))->y;
		if ($age < $limitAge) {
			return false;
		}else{
			return true;
		}
	}

	private function generateRegisterVerifyMethods($post_data, $ignore_registration_settings = false){

		$ignore_registration_settings = false;
		$verify_function_list = [];
		$verify_function_list = [
			// [ 'name' => 'verifyBlockPlayerIp', 'params' => [] ],
			[ 'name' => 'verifyIPLimit', 'params' => [] ],
			[ 'name' => 'isReachDailyIPAllowedRegistrationLimit', 'params' => []],
			[ 'name' => 'verifyIPAccessDailyTimes', 'params' => []],
			[ 'name' => 'verifyIPAccessDailyTimes', 'params' => [] ],
		];
		if(!$ignore_registration_settings){

			$verify_function_list[] = [ 'name' => 'verifyUsernameLimit', 'params' => [$post_data['username']] ];
			$verify_function_list[] = [ 'name' => 'verifyUsernameExist', 'params' => [$post_data['username']] ];
			$verify_function_list[] = [ 'name' => 'verifyPasswordLimit', 'params' => [$post_data['password'], $ignore_registration_settings] ];
			$verify_function_list[] = [ 'name' => 'verifyPasswordIsSameAsUsername', 'params' => [$post_data['password'], $post_data['username']]];
			$verify_function_list[] = [ 'name' => 'verifyPasswordExcludeFields', 'params' => [$post_data] ];
			$verify_function_list[] = [ 'name' => 'verifyFieldFormat', 'params' => [$post_data] ];
			$verify_function_list[] = [ 'name' => 'verifyRegistrationSetting', 'params' => [$post_data] ];

		}

		if(!empty($post_data['contactNumber'])){
			$verify_function_list[] = [ 'name' => 'verifyContactNumberRegistration', 'params' => [$post_data['contactNumber']]];
			$verify_function_list[] = ['name' => 'verifyOTPCodeRegistration', 'params' => [$post_data['otp_source'], null, $post_data['contactNumber'], $post_data['sms_verification_code']]];
		}

		if(!empty($post_data['pix_number'])){
			$verify_function_list[] = [ 'name' => 'verifyCPFNumberExist', 'params' => [$post_data['pix_number']]];
		}

		if(!empty($post_data['email'])){
			$verify_function_list[] = [ 'name' => 'verifyEmailExist', 'params' => [$post_data['email']]];
		}

		if(!empty($post_data['birthdate']) && $post_data['terms'] !== ''){
			$verify_function_list[] = [ 'name' => 'verifyAgeRegistration', 'params' => [$post_data['birthdate'], $post_data['terms']]];
		}

		return $verify_function_list;
	}

	public function generateRegisterPostData($request_body){
		$post_data = [];
		$post_data['username'] = strtolower($request_body['username']);
		$post_data['username_on_register'] = $request_body['username'];
		$post_data['gameName'] = $request_body['username'];
		$post_data['password'] = $request_body['password'];
		$post_data['currency'] = $this->utils->safeGetArray($request_body, 'currency', '');
		$post_data['otp_source'] = $this->utils->safeGetArray($request_body, 'otpSource', self::OTP_SOURCE_SMS);
		$post_data['affTrackingSourceCode'] = $this->utils->safeGetArray($request_body, 'affTrackingSourceCode', '');
		$post_data['firstName'] = trim($this->utils->safeGetArray($request_body, 'firstName', ''));
		$post_data['lastName'] = trim($this->utils->safeGetArray($request_body, 'lastName', ''));
		$post_data['birthdate'] = $this->utils->safeGetArray($request_body, 'birthday', '');
		$post_data['gender'] = isset($request_body['gender'])? $this->playerapi_lib->matchInputGender($request_body['gender']) : '';
		$post_data['citizenship'] = isset($request_body['countryCode'])? $this->playerapi_lib->matchInputCountryCode($request_body['countryCode']) : '';
		$post_data['birthplace'] = $this->utils->safeGetArray($request_body, 'birthplace', '');
		$post_data['language'] = isset($request_body['language'])? $this->playerapi_lib->matchInputLanguage($request_body['language']) : '';
		$post_data['contactNumber'] = $this->utils->safeGetArray($request_body, 'phoneNumber', '');
		$post_data['imAccount'] = $this->utils->safeGetArray($request_body, 'im1', '');
		$post_data['imAccount2'] = $this->utils->safeGetArray($request_body, 'im2', '');
		$post_data['secretQuestion'] = $this->utils->safeGetArray($request_body, 'secretQuestion', '');
		$post_data['secretAnswer'] = $this->utils->safeGetArray($request_body, 'secretAnswer', '');
		$post_data['invitationCode'] = $this->utils->safeGetArray($request_body, 'referralCode', '');
		$post_data['affiliateCode'] = $this->utils->safeGetArray($request_body, 'affTrackingCode', '');
		$post_data['residentCountry'] = isset($request_body['residentCountry'])? $this->playerapi_lib->matchInputCountryCode($request_body['residentCountry']) : '';
		$post_data['sms_verification_code'] = $this->utils->safeGetArray($request_body, 'otpCode', '');
		$post_data['withdrawPassword'] = $this->utils->safeGetArray($request_body, 'withdrawPassword', '');
		$post_data['city'] = $this->utils->safeGetArray($request_body, 'city', '');
		$post_data['region'] = $this->utils->safeGetArray($request_body, 'region', '');
		$post_data['bankName'] = $this->utils->safeGetArray($request_body, 'bankName', '');
		$post_data['bankAccountNumber'] = $this->utils->safeGetArray($request_body, 'bankAccountNumber', '');
		$post_data['bankAccountName'] = $this->utils->safeGetArray($request_body, 'bankAccountName', '');
		$post_data['address'] = $this->utils->safeGetArray($request_body, 'address', '');
		$post_data['address2'] = $this->utils->safeGetArray($request_body, 'address2', '');
		$post_data['address3'] = $this->utils->safeGetArray($request_body, 'address3', '');
		$post_data['email'] = $this->utils->safeGetArray($request_body, 'email', '');
		$post_data['agent_tracking_code'] = $this->utils->safeGetArray($request_body, 'agentTrackingCode', '');
		$post_data['imAccount3'] = $this->utils->safeGetArray($request_body, 'im3', '');
		$post_data['zipcode'] = $this->utils->safeGetArray($request_body, 'zipcode', '');
		$post_data['id_card_number'] = $this->utils->safeGetArray($request_body, 'idCardNumber', '');
		$post_data['dialing_code'] = $this->utils->safeGetArray($request_body, 'countryPhoneCode', '');
		$post_data['id_card_type'] = $this->utils->safeGetArray($request_body, 'idCardType', '');
		$post_data['expiryDate'] = $this->utils->safeGetArray($request_body, 'expiryDate', '');
		$post_data['newsletter_subscription'] = $this->utils->safeGetArray($request_body, 'newsletterSubscription', '');
		$post_data['player_preference_site_notification'] = $this->utils->safeGetArray($request_body, 'playerPreferenceSiteNotification', '');
		$post_data['player_preference_push_notification'] = $this->utils->safeGetArray($request_body, 'playerPreferencePushNotification', '');
		$post_data['pix_number'] = $this->utils->safeGetArray($request_body, 'cpfNumber', '');
		$post_data['imAccount4'] = $this->utils->safeGetArray($request_body, 'im4', '');
		$post_data['imAccount5'] = $this->utils->safeGetArray($request_body, 'im5', '');
		$post_data['issuingLocation'] = $this->utils->safeGetArray($request_body, 'issuingLocation', '');
		$post_data['issuanceDate'] = $this->utils->safeGetArray($request_body, 'issuanceDate', '');
		$post_data['middleName'] = $this->utils->safeGetArray($request_body, 'middleName', '');
		$post_data['maternalName'] = $this->utils->safeGetArray($request_body, 'maternalName', '');
		$post_data['isPEP'] = $this->utils->safeGetArray($request_body, 'isPEP', '');
		$post_data['terms'] = $this->utils->safeGetArray($request_body, 'ageRestrictions', '');
		$post_data['player_preference_email'] = $this->utils->safeGetArray($request_body, 'playerPreferenceEmail', '');
		$post_data['player_preference_sms'] = $this->utils->safeGetArray($request_body, 'playerPreferenceSms', '');
		$post_data['player_preference_phone_call'] = $this->utils->safeGetArray($request_body, 'playerPreferencePhoneCall', '');
		$post_data['player_preference_post'] = $this->utils->safeGetArray($request_body, 'playerPreferencePost', '');
		$post_data['acceptCommunications'] = $this->utils->safeGetArray($request_body, 'acceptCommunications', '');
		$post_data['visitRecordId'] = $this->utils->safeGetArray($request_body, 'visitRecordId', '');
		return $post_data;
	}

	public function postRegisterPlayerAccount($post_data) {
		$this->utils->debug_log('postRegisterPlayerAccount post', $this->utils->filterPasswordLogs($this->input->post()));
		$result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		# third party login
		$ignore_registration_settings = false;
		$thirdPartyLoginType = $this->utils->safeGetArray($post_data, 'thirdPartyLoginType', '');
		if(!empty($thirdPartyLoginType)){
			$this->load->model('third_party_login');
			switch ($thirdPartyLoginType) {
				case Third_party_login::THIRD_PARTY_LOGIN_TYPE_LINE:
					$line_user_id = $this->utils->safeGetArray($post_data, 'line_user_id', '');
					$line_player  = $this->third_party_login->getLinePlayersByUserId($line_user_id);
					if(!empty($line_player)){
						$ignore_registration_settings = true;
					}
					break;
				case Third_party_login::THIRD_PARTY_LOGIN_TYPE_FACEBOOK:
					$facebook_user_id = $this->utils->safeGetArray($post_data, 'facebook_user_id', '');
					$facebook_player  = $this->third_party_login->getFacebookPlayersByUserId($facebook_user_id);
					if(!empty($facebook_player)){
						$ignore_registration_settings = true;
					}
					break;
				case Third_party_login::THIRD_PARTY_LOGIN_TYPE_GOOGLE:
					$google_user_id = $this->utils->safeGetArray($post_data, 'google_user_id', '');
					$google_player  = $this->third_party_login->getGooglePlayersByUserId($google_user_id);
					if(!empty($google_player)){
						$ignore_registration_settings = true;
					}
					break;

				default:
					break;
			}
		}
		$verify_function_list = $this->generateRegisterVerifyMethods($post_data, $ignore_registration_settings);
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============postRegisterPlayerAccount verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
			if(!$exec_continue) {
				$result['errorCode'] = isset($verify_result['error_code']) ? $verify_result['error_code'] : $result['errorCode'];
				$result['errorMessage'] = $verify_result['error_message'];
				throw new APIException($result['errorMessage'], $result['errorCode']);
			}
		}

		# LIBRARIES
		$this->load->library(array('session', 'sms/sms_sender'));
		$this->load->model(array('group_level', 'agency_model','communication_preference_model', 'gbg_logs_model', 'email_template_model', 'acuris_logs_model'));

		/*
		# third party login
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

                default:
                    break;
            }
        }
		*/

		//add tracking banner
		$visit_record_id = $this->session->userdata('visit_record_id');
		//add for playercenter api
		$post_visit_record_id = $this->utils->safeGetArray($post_data, 'visitRecordId', '');
		$this->utils->info_log('============postRegisterPlayerAccount visit_record_id', $visit_record_id, $post_visit_record_id);
		if(empty($visit_record_id) && !empty($post_visit_record_id)){
			$this->load->model(['http_request']);
			$validVisitRecord = $this->http_request->isValidVisitRecord($post_visit_record_id);
			if(!empty($validVisitRecord)){
				$visit_record_id = $post_visit_record_id;
			}
		}

		// -- Process Communication Preference
		$config_prefs = $this->utils->getConfig('communication_preferences');
		$communication_preference = null;
		if($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($config_prefs)){
			$pp_email = ($post_data['player_preference_email'] !== '')? $post_data['player_preference_email'] : false;
			$pp_sms = ($post_data['player_preference_sms'] !== '')? $post_data['player_preference_sms'] : false;
			$pp_phone_call = ($post_data['player_preference_phone_call'] !== '')? $post_data['player_preference_phone_call'] : false;
			$pp_post = ($post_data['player_preference_post'] !== '')? $post_data['player_preference_post'] : false;
			$post_preference = [
				'pref-data-email' => $this->utils->boolToStringBool($pp_email),
				'pref-data-sms' => $this->utils->boolToStringBool($pp_sms),
				'pref-data-phone_call' => $this->utils->boolToStringBool($pp_phone_call),
				'pref-data-post' => $this->utils->boolToStringBool($pp_post),
			];
			$communication_preference = $this->communication_preference_model->getCommunicationPreferenceChanges($post_preference);
		}

		if($this->config->item('allow_first_number_zero') && substr($post_data['contactNumber'],0,1) == '0'){
			$post_data['contactNumber'] = substr($post_data['contactNumber'],1);
		}

		$this->load->model(['affiliatemodel']);
		$theAffiliat = [];
		$tracking_code = $post_data['affiliateCode'];
		$invitation_code = $post_data['invitationCode'];

		if(empty($tracking_code) && empty($invitation_code)){
			$tracking_code=$this->getTrackingCode();
			$this->utils->debug_log('postRegisterPlayerAccount', 'tracking_code is empty, generate new tracking_code:', $tracking_code);
		}

		$validate_code = true;
		if($this->utils->getConfig('registration_time_aff_tracking_code_validation') ||$this->utils->isEnabledFeature('enable_registration_time_aff_tracking_code_validation')){
			$validate_code = $this->check_aff_tracking_code_is_aff_active($tracking_code);
		}
		if($validate_code) {
			$tracking_source_code = $post_data['affTrackingSourceCode'];
		} else {
			$tracking_code = null;
			$tracking_source_code = null;
		}
		if(!empty($tracking_code )){
			$theAffiliat = $this->affiliatemodel->getAffByTrackingCode($tracking_code);
			$disable_cashback_on_registering = $this->utils->safeGetArray($theAffiliat, 'disable_cashback_on_registering', false); //$theAffiliat['disable_cashback_on_registering'];
			$disable_promotion_on_registering = $this->utils->safeGetArray($theAffiliat, 'disable_promotion_on_registering', false); //$theAffiliat['disable_promotion_on_registering'];
		}else{
			$disable_cashback_on_registering = false;
			$disable_promotion_on_registering = false;
		}
		$this->utils->debug_log('postRegisterPlayerTheAffiliat', $theAffiliat, '$tracking_code:', $tracking_code, '$tracking_source_code:', $tracking_source_code);
		$httpHeadrInfo = $this->session->userdata('httpHeaderInfo') ?: $this->utils->getHttpOnRequest();
        $header_referrer = preg_replace('/\s+/', '', $httpHeadrInfo['referrer']);
        $referrer = $header_referrer ?: $this->utils->safeGetArray($_SERVER, 'HTTP_REFERER', '');

		if(!empty($post_data['contactNumber']) && !empty($post_data['sms_verification_code'])){
			$verified_phone = true;
		}else{
			$verified_phone = false;
		}

		if($this->utils->getConfig('enable_default_country_when_post_dialing_code')){
			if(empty($post_data['citizenship']) && !empty($profileData['dialing_code'])){
				$post_data['citizenship'] = $this->getCountryCodeByPhoneCode($post_data['dialing_code']);
			}
		}

		$player_data = array(
			# Player
			'username' => $post_data['username'],
			'gameName' => $post_data['gameName'],
			'email' => $post_data['email'],
			'password' => $post_data['password'],
			'secretQuestion' => $post_data['secretQuestion'],
			'secretAnswer' => $post_data['secretAnswer'],
			'verify' => '',
			'withdraw_password' => $post_data['withdrawPassword'],

			# Player Details
			'firstName' => $post_data['firstName'],
			'lastName' => $post_data['lastName'],
			'language' => $post_data['language'],
			'gender' => $post_data['gender'],
			'birthdate' => $post_data['birthdate'],
			'contactNumber' => $post_data['contactNumber'],
			'citizenship' => $post_data['citizenship'],
			'imAccount' => $post_data['imAccount'],
			'imAccountType' => '',
			'imAccount2' => $post_data['imAccount2'],
			'imAccountType2' => '',
			'imAccount3' => $post_data['imAccount3'],
			'imAccountType3' => '',
			'imAccount4' => $post_data['imAccount4'],
			'imAccountType4' => '',
			'imAccount5' => $post_data['imAccount5'],
			'imAccountType5' => '',
			'birthplace' => $post_data['birthplace'],
			'registrationIp' =>  $this->utils->getIP(),
			'registrationWebsite' => $referrer,
			'residentCountry' => $post_data['residentCountry'],
			'city' => $post_data['city'],
			'address' =>  $post_data['address'],
			'address2' => $post_data['address2'],
			'address3' => $post_data['address3'],
			'zipcode' => $post_data['zipcode'],
			'dialing_code' => $post_data['dialing_code'],
			'id_card_type' => $post_data['id_card_type'],
			'id_card_number' => $post_data['id_card_number'],
			'pix_number' =>  $post_data['pix_number'],

			# Player Details Extra
			'middleName' => $post_data['middleName'],
			'maternalName' => $post_data['maternalName'],
			'issuingLocation' => $post_data['issuingLocation'],
			'issuanceDate' => $post_data['issuanceDate'],
			'expiryDate' => $post_data['expiryDate'],
			'isPEP' => intval($post_data['isPEP']),
			'acceptCommunications' => intval($post_data['acceptCommunications']),

            // player_preference
			'username_on_register' => $post_data['username_on_register'],

			# Codes
			'tracking_code' => $tracking_code,
			'tracking_source_code' => $tracking_source_code,
			'agent_tracking_code' => $post_data['agent_tracking_code'],
			'agent_tracking_source_code' => null,

			# SMS verification
			// 'otp_source' => $otp_source,
			// 'otp_verification_code' => $otp_verification_code,
			'verified_phone' => $verified_phone,

			# from affiliates
			'disable_promotion_on_registering' => $disable_promotion_on_registering,
			'disable_cashback_on_registering' => $disable_cashback_on_registering,

			'visit_record_id' => $visit_record_id,
			'newsletter_subscription' => intval($post_data['newsletter_subscription']),
			'communication_preference' => $communication_preference,
		);

		$this->utils->removeReferralCodeCookie();
		$this->utils->removeBtagCookie();
		$refereePlayerId = null;
		if (!empty($invitation_code)){
			$refereePlayerId = $this->player_model->getPlayerIdByReferralCode($invitation_code);
		}
		if (!empty($refereePlayerId)){
			$player_data['refereePlayerId'] = $refereePlayerId;
			$player_data['referral_code'] = $invitation_code;
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
			if(!empty($line_player['line_user_id'])){
				$data['player_id'] = $playerId;
				$this->third_party_login->updateLinePlayersByUserId($line_player['line_user_id'], $data);
			}
			if(!empty($facebook_player['facebook_user_id'])){
				$data['player_id'] = $playerId;
				$this->third_party_login->updateFacebookPlayersByUserId($facebook_player['facebook_user_id'], $data);
			}
			if(!empty($google_player['google_user_id'])){
				$data['player_id'] = $playerId;
				$this->third_party_login->updateGooglePlayersByUserId($google_player['google_user_id'], $data);
			}
			//sync
			$this->syncPlayerCurrentToMDBWithLock($playerId, $post_data['username'], true);
			// refresh wallet on mdb
			if($this->utils->isEnabledMDB()) {
				// load model multiple_db_model
				$this->load->model(['multiple_db_model']);
				$succ=$this->multiple_db_model->refreshAllCurrencyWalletsForPlayer($playerId);
				if(!$succ){
					$this->utils->error_log('refreshAllCurrencyWalletsForPlayer failed', $playerId);
				}
			}
			// -- Save HTTP Request after lock/transaction
			$this->load->model(['http_request','player_attached_proof_file_model']);
			$this->utils->saveHttpRequest($playerId, Http_request::TYPE_REGISTRATION);

			// -- save default attached file status history
			$this->player_attached_proof_file_model->saveAttachedFileStatusHistory($playerId);
		}

		if($this->utils->isEnabledFeature('enable_player_registered_send_msg')) {
			$this->load->model(['cms_model', 'player_model', 'queue_result']);
			$this->load->library(["lib_queue"]);
			$sms_template = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_REGISTERED);

			$searchArr=['{player_username}', '{player_center_url}', '{mobile_number}'];
			$replaceArr=[ $post_data['username'], $this->utils->getSystemUrl('player'), $post_data['contactNumber']];
			$content = str_replace($searchArr, $replaceArr, $sms_template);
			$mobileNum = !empty($dialing_code)? $dialing_code.'|'.$post_data['contactNumber'] : $post_data['contactNumber'];
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

		if ($this->utils->getConfig('send_msg_to_remind_change_password')['add_player_password_history_after_register']) {
			$this->load->model('player', 'player_model');
			$player = $this->player->getPlayerByPlayerId($playerId);
			$password = isset($player['password']) ? $player['password'] : '';
			$this->player_model->insertPasswordHistory($playerId, Player_model::RESET_PASSWORD, $password);
		}

		$bank_name = $post_data['bankName'];
		$bank_account_num = $post_data['bankAccountNumber'];
		$bank_account_name = $post_data['bankAccountName'];
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
		$this->utils->clearTrackingCode();
		$this->utils->clearAgentTrackingCodeFromSession();
		$this->utils->clearAgentTrackingSourceCodeFromSession();

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
		$levels_options = '';
		if ($levels_options){ // if select level options
			$this->group_level->startTrans();
			$this->group_level->adjustPlayerLevel($playerId, $levels_options);
			$this->group_level->endTrans();
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
		// trigger register event
		// meta
		$extraInfo = [
			'source_url'       => $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
			'client_user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'client_ip_address' => $this->utils->getIP(),
		];
		$this->triggerRegisterEvent($playerId);

		$this->utils->playerTrackingEventForS2S($playerId, 'TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM', $extraInfo);

		$pixSystemInfo = $this->utils->getConfig('pix_system_info');
		if($pixSystemInfo['auto_build_pix_account']['enabled']){
			$bank_details_ids = $this->autoBuildPlayerPixAccount($playerId);
			$this->utils->debug_log("============auto_build_pix_account_type", $bank_details_ids);
		}

		$result['success'] = true;
		$result['id'] = $playerId;
		$result['message'] = lang('notify.9');
		return $result;
	}

	private function generateCaptcha($image_width, $image_height) {
		$result = [
			'captchaCode' => '',
			'captchaKey' => '',
		];

		$active = $this->config->item('si_active');
		$allsettings['image_width'] = $image_width;
		$allsettings['image_height'] = $image_height;
		$allsettings = array_merge($this->config->item($active), $this->config->item('si_general'), ['namespace' => 'default']);
		$allsettings['no_exit'] = true; // for $b64im
		$this->load->library('captcha/securimage');
		$img = new Securimage($allsettings);

		$b64im = [];
		$img->show($this->config->item('si_background'), $b64im); // it will ignore output directly ,when $b64im is an empty array.

		// $result['captchaCode'] = sprintf('data:%s;base64, %s', $b64im['content_type'], $b64im['b64im']); // for <img src="..."/>
		$result['captchaCode'] = $b64im['b64im'];
		$result['captchaKey'] = $b64im['token'];
		// $result['expiry_sec'] = $b64im['expiry_sec'];

		// $api_key = $this->input->post('api_key');
		// if( ! empty($api_key) ){ // for QA
		//     $internal_player_center_api_key = $this->config->item('internal_player_center_api_key');
		//     if($api_key == $internal_player_center_api_key){
		        // $resquest_data['img'] = sprintf('<img src="data:%s;base64, %s" />', $b64im['content_type'], $b64im['b64im']);
		//     }
		// }

		// $message = lang('The captcha image had generated.');
		// $responseCode = self::CODE_SUCCESS;
		return $result;
	}

	public function verifyLoginPriv($username){
		$this->utils->debug_log('start verifyLoginPriv username', $username);
		$this->load->model(['player_model']);
		$this->load->library([ 'language_function', 'player_library' ]);

		$result = [
			'errorCode' => '',
			'errors' => [],
			'success' => true
		];

		$player_id = $this->player_model->getPlayerIdByUsername($username);
		$result = $this->player_library->_check_player($result, $player_id);

		$enable_restrict_username_more_options = !empty($this->utils->getConfig('enable_restrict_username_more_options'));
		$username_on_register = null; // default
		$usernameRegDetails = []; // for collect.
		if( ! empty($player_id) ){
			$username_on_register = $this->player_library->get_username_on_register($player_id, $usernameRegDetails);
		}

		if( empty($usernameRegDetails['username_case_insensitive']) && $enable_restrict_username_more_options && !empty($username_on_register)){ // Case Sensitive
			if ( $username_on_register != $username) {
				$result['success'] = FALSE;
				$result['errors']['login'] = lang('con.04');
			}
		}

		if (!$result['success']) {
			$errors = $result['errors'];
			if (isset($errors['blocked'])) {
				$result['errorCode'] = Playerapi::CODE_PLAYER_BLOCKED;
			}
			else if (isset($errors['login'])) {
				$result['errorCode'] = Playerapi::CODE_LOGIN_FAILED;
			}
			else if (isset($errors['selfexclusion'])) {
				$result['errorCode'] = Playerapi::CODE_LOGIN_USER_UNDER_SELF_EXCLUSION;
			}
			else {
				$result['errorCode'] = Playerapi::CODE_LOGIN_FAILED;
			}
		}

		$this->utils->debug_log('end verifyLoginPriv priv', $result);
		return $result;
	}

	public function processLoginExtra($username, $login_from_player = true){

		$this->utils->info_log("start processLoginExtra username[$username]");
		$this->load->model(['player_model']);
		$this->load->library([ 'salt', 'language_function', 'player_library' ]);

		$player_id = $this->player_model->getPlayerIdByUsername($username);
		$this->player_model->updateLoginInfo($player_id, $this->input->ip_address(), $this->utils->getNowForMysql());
		if( ! empty($player_id) ){
			$login_referrer   	= trim($this->input->post('login_referrer', 1));
			$login_referrer   	= filter_var($login_referrer, FILTER_SANITIZE_URL);
			$login_ip         	= trim($this->input->post('login_ip', 1));
			$login_ip         	= filter_var($login_ip, FILTER_VALIDATE_IP);
			$registration_ip 	= empty($login_ip) ? $this->utils->getIP() : $login_ip;
			$httpHeadrInfo      = $this->utils->getHttpOnRequest();
			$header_referrer    = preg_replace('/\s+/', '', $httpHeadrInfo['referrer']);
			$referrer           = $login_referrer ?: ($login_ip ?: $header_referrer);
			$extra = [
				'ip'            => $registration_ip ,
				'user_agent'    => !empty($login_user_agent) ? $login_user_agent : '',
				'referrer'      => $referrer,
			];
			$source_method = __METHOD__;
			$this->utils->saveHttpRequest($player_id, Http_request::TYPE_LAST_LOGIN, $extra);
			if ($this->utils->getConfig('enable_player_login_report')) {
				$this->load->model(array('player_login_report'));
				$login_from = $login_from_player ? Player_login_report::LOGIN_FROM_PLAYER : Player_login_report::LOGIN_FROM_ADMIN;

				$result = array("success" => true, "player_id" => $player_id);
				$this->utils->savePlayerLoginDetails($player_id, $username, $result, $login_from, $extra);
				$this->utils->debug_log(__METHOD__,'savePlayerLoginDetails', $result);
			}
			$this->player_library->triggerPlayerLoggedInEvent( $player_id, $source_method);
		}
	}

	private function getCurrentVipGroupInfo($playerId = null, $additional=[]){
		$cache_setting = $this->utils->getCacheSettingOnConfig('player_current_vip_group_info');

		if($cache_setting['enable']){
			$cache_key = "player_current_vip_group_info_{$playerId}";
			$cached_result = $this->utils->getJsonFromCache($cache_key);
			if(!empty($cached_result)) {
				$this->utils->debug_log(__METHOD__, "getCurrentVipGroupInfo from cache", ['cache_key' => $cache_key, 'cached_result' => $cached_result]);
				return $cached_result;
			}
		}

		$vipGroupInfo = [];

		if (empty($playerId)) {
			$groupId = $additional['default_group_id'];
		} else {
			$groupId = $this->playerapi_model->getGroupIdByPlayerId($playerId);
		}

		$vipGroupInfo = $this->playerapi_model->getVipGroupInfo($groupId);

		if(!empty($vipGroupInfo) && $additional['vip_upgrade_setting']){
			foreach ($vipGroupInfo as $index => $vip_item) {
				$period_upgrade   = isset($vip_item['period_up_down_2'])? $vip_item['period_up_down_2']:[];
				$period_downgrade = isset($vip_item['period_down'])? $vip_item['period_down']:[];

				$vipGroupInfo[$index]['vip_upgrade']   = $this->wrapVipUpgradeGroupSetting($vip_item['vip_upgrade_id'], $period_upgrade);
	        	$vipGroupInfo[$index]['vip_downgrade'] = $this->wrapVipUpgradeGroupSetting($vip_item['vip_downgrade_id'], $period_downgrade);
			}
		}

		if($cache_setting['enable']){
			$ttl = $cache_setting['ttl'];
			$this->utils->saveJsonToCache($cache_key, $vipGroupInfo, $ttl);
		}

		return $vipGroupInfo;
	}

	private function getCurrentVipLevelInfo($playerId, $additional=[]){
		$player_vip_list = $this->playerapi_model->getPlayerVipList($playerId);

		if(!empty($player_vip_list) && $additional['vip_upgrade_setting']){
			$period_upgrade   = isset($player_vip_list['period_up_down_2'])? $player_vip_list['period_up_down_2']:[];
			$period_downgrade = isset($player_vip_list['period_down'])? $player_vip_list['period_down']:[];

	        $player_vip_list['vip_upgrade']   = $this->wrapVipUpgradeGroupSetting($player_vip_list['vip_upgrade_id'], $period_upgrade);
	        $player_vip_list['vip_downgrade'] = $this->wrapVipUpgradeGroupSetting($player_vip_list['vip_downgrade_id'], $period_downgrade);
		}

		return $player_vip_list;
	}

	private function wrapVipUpgradeGroupSetting($upgrade_id, $period_setting){
		$upgrade_info 		     	 = $this->playerapi_model->getVipUpGroupSetting($upgrade_id);
		$accumulation     			 = isset($upgrade_info['accumulation'])? $upgrade_info['accumulation'] : null;
		$formula   		  			 = isset($upgrade_info['formula'])? json_decode($upgrade_info['formula'], true) : '';
		$period_setting   		  	 = !empty($period_setting)? json_decode($period_setting, true):[];
		$wrap_accumulation_upgrade   = $this->playerapi_lib->matchAccumulationModeOnVIP($accumulation, $period_setting);

		$upgrade_info['formula'] = $formula;
		$upgrade_info['type']    = $wrap_accumulation_upgrade;
		return $upgrade_info;
	}

	private function getVIPCurrentTotalDepositAndBets($player_id){
		$cache_setting = $this->utils->getCacheSettingOnConfig('player_vip_current_total_deposit_and_bet');

		if($cache_setting['enable']){
			$cache_key = "player_vip_current_total_deposit_and_bet_{$player_id}";
			$cached_result = $this->utils->getJsonFromCache($cache_key);
			if(!empty($cached_result)) {
				$this->utils->debug_log(__METHOD__, "getVIPCurrentTotalDepositAndBets from cache", ['cache_key' => $cache_key, 'cached_result' => $cached_result]);
				return $cached_result;
			}
		}

		$vip_additional  = ['vip_upgrade_setting' => true];
        $currency = $this->utils->getActiveCurrencyKeyOnMDB();
        if($this->utils->isEnabledMDB()){
            $player_vip_list = $this->playerapi_lib->switchCurrencyForAction( $currency, function() use ($player_id, $vip_additional) {
                return $this->getCurrentVipLevelInfo($player_id, $vip_additional);
                // return $this->getDepositAccountsByPlayerId($player_id);
            });
        }else{
            $player_vip_list = $this->getCurrentVipLevelInfo($player_id, $vip_additional);
        }



		if(empty($player_vip_list)){
			throw new APIException('Player not found.', Playerapi::CODE_PLAYER_NOT_FOUND);
		}

		$vip_upgrade               = $player_vip_list['vip_upgrade'];
		$bet_amount_settings       = isset($vip_upgrade['bet_amount_settings'])? $vip_upgrade['bet_amount_settings'] : null;
		$wrap_accumulation_upgrade = isset($vip_upgrade['type'])? $vip_upgrade['type'] : null;
		$date_range                = $this->getAccumulationDateRange($wrap_accumulation_upgrade);

		$result = ['playerTotalDeposit' => 0, 'playerTotalBetting' => 0];

		if(!empty($date_range)){
			$this->load->model(['transactions']);
            $this->load->library(array('group_level_lib'));
            $enable_multi_currencies_totals = $this->utils->getConfig('enable_multi_currencies_totals');

            if($this->utils->isEnabledMDB() && !empty($enable_multi_currencies_totals) ) {
                $playerTotalDeposit = $this->playerapi_lib->switchCurrencyForAction( $currency, function() use ($player_id, $date_range) {
                    return $this->group_level_lib->getPlayerTotalDepositsWithForeachMultipleDBWithoutSuper($player_id, $date_range['start'], $date_range['end']);
                });
            }else{
                $playerTotalDeposit = $this->transactions->getPlayerTotalDeposits($player_id, $date_range['start'], $date_range['end']);
            }
			$result['playerTotalDeposit'] = floatval($playerTotalDeposit);

            if($this->utils->isEnabledMDB()){
                $playerTotalBetting = $this->playerapi_lib->switchCurrencyForAction( $currency, function() use ($player_id, $date_range, $bet_amount_settings) {
                    return $this->calculatePlayerTotalBet($player_id, $date_range, $bet_amount_settings);
                });
            }else{
                $playerTotalBetting = $this->calculatePlayerTotalBet($player_id, $date_range, $bet_amount_settings);
            }
			$result['playerTotalBetting'] = $playerTotalBetting;
		}

		if($cache_setting['enable']){
			$ttl = $cache_setting['ttl'];
			$this->utils->saveJsonToCache($cache_key, $result, $ttl);
		}

		return $result;
	}

	/**
     * reference by getPlayerBetAmtForNextLvl in group_level
     * @return [int] total bets
     */
    private function calculatePlayerTotalBet($player_id, $date_range, $bet_amount_settings)
    {
    	$this->load->model(['total_player_game_day','group_level']);
        $this->load->library(array('group_level_lib'));
		$start_date_mins = $this->utils->formatDateMinuteForMysql(new DateTime($date_range['start']));
		$end_date_mins   = $this->utils->formatDateMinuteForMysql(new DateTime($date_range['end']));

        $enable_multi_currencies_totals = $this->utils->getConfig('enable_multi_currencies_totals');
        if( $this->utils->isEnabledMDB() && !empty($enable_multi_currencies_totals) ) {
            $total_player_game_table = 'total_player_game_minute';
            $where_date_field = 'date_minute';
            $where_game_platform_id = null;
            $where_game_type_id = null;
            $game_log_data = $this->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper( $player_id // #1
                                                                                                , $start_date_mins // #2
                                                                                                , $end_date_mins // #3
                                                                                                , $total_player_game_table // #4
                                                                                                , $where_date_field  // #5
                                                                                                , $where_game_platform_id // #6
										                                                        , $where_game_type_id // #7
                                                                                            );
        }else{
            $game_log_data   = $this->total_player_game_day->getPlayerTotalBetWinLoss($player_id, $start_date_mins, $end_date_mins, 'total_player_game_minute', 'date_minute');
        }

		if( is_null($bet_amount_settings) ){
			$bet_amount_settings = '{}';
		}

		if( is_string($bet_amount_settings) ){
			$isValidJon = $this->utils->isValidJson($bet_amount_settings);
			if($isValidJon){
				$bet_amount_settings = json_decode($bet_amount_settings, true);
			}
		}

		$separatedGameLogData = $this->group_level->getSeparatedGameLogDataFromTotalPlayerGameMinute($player_id, $date_range['start'], $date_range['end'], $bet_amount_settings);

		if(!empty($separatedGameLogData)){
            $total_bet = 0;
			foreach($separatedGameLogData as $indexNumber => $currGameLogData){
				$currMathSign = $currGameLogData['math_sign'];
				$currValue = floatval($currGameLogData['result_amount']);
				switch($currMathSign){
					case '>=':
					case '==':
						$total_bet += $currValue;
					break;
					case '>':
						$total_bet += $currValue;
						$total_bet++;
					break;
					case '<':
					case '<=':
						$total_bet += 0;
					break;
				}
			}
            $game_log_data['total_bet'] = $total_bet;
		}

		return $game_log_data['total_bet'];
    }

    private function getAccumulationDateRange($accumulation_mode)
    {
    	$this->load->model(['group_level']);
    	$data_range = [];
    	switch ($accumulation_mode) {
    		case Playerapi::CODE_VIP_RANGE_YESTERDAY:
    			$data_range['start'] = $this->utils->getYesterdayForMysql().' '.Utils::FIRST_TIME;
				$data_range['end']   = $this->utils->getYesterdayForMysql().' '.Utils::LAST_TIME;
    		break;
    		case Playerapi::CODE_VIP_RANGE_LASTER_WEEK:
				$week_start = 'sunday';
                $previous_week = strtotime("-1 week +1 day", strtotime($this->utils->getNowForMysql()));
                $dt_start = new DateTime();
                $dt_start->setTimestamp(strtotime('last ' . $week_start . ' midnight', $previous_week));
                $dt_end = new DateTime();
                $dt_end->setTimestamp(strtotime('last ' . $week_start . ' +6 day', $previous_week));
                $data_range['start'] = $this->utils->formatDateForMysql($dt_start).' '.Utils::FIRST_TIME;
                $data_range['end'] = $this->utils->formatDateForMysql($dt_end).' '.Utils::LAST_TIME;
			break;
    		case Playerapi::CODE_VIP_RANGE_LAST_MONTH:
    			$data_range['start'] = date('Y-m-d', strtotime('first day of last month')).' '.Utils::FIRST_TIME;
				$data_range['end']   = date('Y-m-d', strtotime('last day of last month')).' '.Utils::LAST_TIME;
    		break;
    		case Playerapi::CODE_VIP_RANGE_FROM_REGISTRATION:
    			$now = new DateTime();
    			$playerDetails = $this->player_model->getPlayerDetailsById($this->player_id);
    			$data_range['start'] = $playerDetails->createdOn;
    			$data_range['end']   = $this->utils->formatDateTimeForMysql($now);
    		break;
    		case Playerapi::CODE_VIP_RANGE_FROM_VIP_START:
    			$now = new DateTime();
    			$playerDetails = $this->player_model->getPlayerDetailsById($this->player_id);
    			$theLastGradeRecordRow = $this->group_level->queryLastGradeRecordRowBy($this->player_id, $playerDetails->createdOn, $this->utils->formatDateTimeForMysql($now), 'upgrade_or_downgrade');

				if( empty($theLastGradeRecordRow) ){
					$fromRange = $playerDetails->createdOn; // from registaction for into vip1 first
				}else{
					$fromRange = $theLastGradeRecordRow['pgrm_end_time'];
				}
    			$data_range['start'] = $fromRange;
    			$data_range['end']   = $this->utils->formatDateTimeForMysql($now);
    		break;
    	}

    	return $data_range;
    }

	private function setProfilePicture($player_id) {
		if ($this->hasUploadedProfilePicture($player_id)) {
			return $this->utils->getSystemUrl("player", str_replace('/mobile', '',$this->getProfilePictureUploadPath()) . '/' . $this->getActiveProfilePicture($player_id));
		} elseif (!empty($this->getActiveProfilePicture($player_id))){
			return str_replace("default_avatar_", "", $this->getActiveProfilePicture($player_id));
		} else {
			if($this->CI->agent->is_mobile()){
				return $this->utils->getSystemUrl("player", $this->utils->getPlayerCenterTemplate().'/images/user_icon.svg');
			} else {
				return $this->utils->getSystemUrl("player", $this->utils->getPlayerCenterTemplate() . '/img/default-profile.png');
			}
		}
	}

	private function hasUploadedProfilePicture($player_id) {
		$profile_filename = $this->getActiveProfilePicture($player_id);
		$file_loc = $this->getProfilePictureFullPath() . '/'. $profile_filename;
		if($this->CI->agent->is_mobile()){
			$file_loc = str_replace('/mobile', '', $file_loc);
		}

		if (!isset($profile_filename) || empty($profile_filename) || !file_exists($file_loc)) { return false; }

		return true;
	}

	private function getActiveProfilePicture($player_id) {
		// Check player proof_filename
		$this->load->model(array('player_model','player_attached_proof_file_model'));
		//NEW Function jhunel.php.ph 1-9-2018
		$response = $this->player_attached_proof_file_model->getAttachementRecordInfo($player_id,null,player_attached_proof_file_model::PROFILE_PICTURE,null,false,null,false);
		if(!empty($response)){
			foreach ($response as $key => $value) {
				if(isset($value['visible_to_player'])){
					if($value['visible_to_player']){
						if(isset($value['file_name'])) {
							return $value['file_name'];
						}
					}
				}
			}
		}

		return false;
	}

	private function getProfilePictureUploadPath() {
		//new function, jhunel.php 1-9-2018
		$path='upload/' . $this->config->item("player_upload_folder");
		$this->utils->addSuffixOnMDB($path);
		return $path;
		/*OLD function
		return 'upload/player/profile_picture/' . $this->utils->getPlayerCenterTemplate();
		*/
	}

	private function getProfilePictureFullPath() {
		//NEW Function jhunel.php 1-9-2018
		$path=$this->utils->getUploadPath() . '/'. $this->config->item("player_upload_folder");
		$this->utils->addSuffixOnMDB($path);
		return $path;
		/*old
		return $this->utils->getUploadPath() . '/player/profile_picture/'. $this->utils->getPlayerCenterTemplate();*/
	}

    /**
	 * @author Hayme.php 2017-05-10
	 * Overview : Get player account information progress
	 * @param 	none
	 * @return	int
	 */
	public function getProfileProgress($player_id) {
		$totalFields =$this->getRequiredAndVisibleFieldsSettings();
        $countFields = count($totalFields);

		// If all player registaration settings are hidden and not required
		if (!$countFields) {
			return 100;
		}

		$totalFieldsWithValue = $this->getTotalFieldWithValue($player_id, $totalFields);

		return $progressPercentage = round(($totalFieldsWithValue / $countFields) * 100);
	}

    protected function getRequiredAndVisibleFieldsSettings() {
		$this->load->model(array('registration_setting'));
		$regSettings = $this->registration_setting->getRegistrationFields();
		$excludedInAccountSettings = $this->utils->getConfig('excluded_in_account_info_settings');

		$fields = array();

		foreach ($regSettings as $key => $value) {
			if ($regSettings[$key]['type'] == 1 && $regSettings[$key]['account_visible'] == '0' && $regSettings[$key]['account_required'] == '0' &&
				$regSettings[$key]['alias'] && !in_array($regSettings[$key]['alias'], $excludedInAccountSettings))
			{

				if ($regSettings[$key]['alias'] == 'bankAccountName') {
					$regSettings[$key]['alias'] = 'bankAccountFullName';
				}

				if ($regSettings[$key]['alias'] == 'city') {
					$regSettings[$key]['alias'] = 'a.city';
				}

				if ($regSettings[$key]['alias'] == 'withdrawPassword') {
					$regSettings[$key]['alias'] = 'withdraw_password';
				}

				if ($regSettings[$key]['alias'] == 'affiliateCode') {
					$regSettings[$key]['alias'] = 'affiliateId';
				}

				array_push($fields, $regSettings[$key]['alias']);
			}
		}

		return $fields;
	}

    protected function getTotalFieldWithValue($player_id, $fields = array()) {
		$fields = implode(", ", $this->getRequiredAndVisibleFieldsSettings());

		if (empty($fields)) {
			return 0;
		}

		$this->load->model(array('player_model'));
		$result =  $this->player_model->getPlayerProfileProgres($fields, $player_id);

		// Get total number of fields with value (not 0, null, "")
		$counter = 0;

		if(empty($result)){
		    return 0;
        }

		foreach ($result as $key => $value) {
			if ($result[$key]) {
				$counter++;
			}
		}

		return $counter;
	}

	private function updatePasswordAndResetExpire($player_id, $new_password) {
		$update_result = [
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
		try {
			$this->load->library(array('salt'));
			$encrypted_password = $this->salt->encrypt($new_password, $this->getDeskeyOG());
			$this->player->editPlayer(array(
				'password' => $encrypted_password,
				'resetCode' => null,
				'resetExpire' => null,
			), $player_id);

			//save changes to playerupdatehistory
			$username = $this->player_model->getUsernameById($player_id);
			$this->player_model->savePlayerUpdateLog($player_id, lang('system.word8'), $username);

			$update_result['message'] = lang('forgot.12');
			$update_result['success'] = true;
			return $update_result;
		} catch (Exception $ex) {
			$update_result['message'] = 'Unknown Error during '.__METHOD__;
			$update_result['errorCode'] = self::CODE_SERVER_ERROR;
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			return $update_result;
		}
	}

	private function verifyEmptyPlayerBankDetail($playerBankDetail) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		if (empty($playerBankDetail)) {
			$message = sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'));
			$verify_result['error_code'] = self::CODE_PLAYER_BANK_ACCOUNT_NOT_FOUND;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
	}

	private function verifyDuplicateAccountNumber($playerId, $bank_account_number, $bank_type, $is_new = TRUE) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = lang('account_number_can_not_be_duplicate');
			if($is_new) {
				if (!$this->utils->getConfig('hide_financial_account_ewallet_account_number')) {
					$exist_duplicate_account = $this->playerbankdetails->validate_bank_account_number($playerId, $bank_account_number, $bank_type);
					if(!$exist_duplicate_account) {
						$verify_result['error_code'] = self::CODE_PLAYER_BANK_ACCOUNT_ALREADY_EXISTS;
						$verify_result['error_message'] = $message;
						$verify_result['passed'] = false;
					}
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyPlayerBankEditable($bank_detail_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = lang('Not allow edit');
			$check_editable = $this->checkPlayerBankEditable($bank_detail_id);
			if(!$check_editable) {
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyBankTypeAddable($bank_type_id){
		$verify_result = ['passed' => true, 'error_message' => ''];
		$bankType = $this->banktype->getBankTypeById($bank_type_id);
		try {
			$message = lang('Not allow add');
			$check_addable = $this->checkBankTypeAddable($bankType);
			if(!$check_addable) {
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyAllowDeleteBankAccountPermission() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		if (!$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_delete')){
			$message = lang('Sorry, no permission');
			$verify_result['error_code'] = self::CODE_UNAUTHORIZED;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
	}

	private function verifyAllowAddWithdrawalBankDetail($all_banks, $input_banktype_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = '';
			if (!Playerbankdetails::AllowAddBankDetail(Playerbankdetails::WITHDRAWAL_BANK, $all_banks, $message, $input_banktype_id)){
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyAllowAddDepositBankDetail($all_banks, $input_banktype_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = '';
			if (!Playerbankdetails::AllowAddBankDetail(Playerbankdetails::DEPOSIT_BANK, $all_banks, $message, $input_banktype_id)){
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyAllowModifyAccountName($payment_type_flag, $oldBankAccountFullName, $newBankAccountFullName) {
		try {
			$this->load->model(array('financial_account_setting'));
			$verify_result = ['passed' => true, 'error_message' => ''];
			$financial_account_rule = $this->financial_account_setting->getPlayerFinancialAccountRulesByPaymentAccountFlag($payment_type_flag);
			$field_show = explode(',', $financial_account_rule['field_show']);
			$allow_modify_name  = $financial_account_rule['account_name_allow_modify_by_players'];
			if ($field_show[0]) {
				if (!$allow_modify_name) {
					if ($oldBankAccountFullName != $newBankAccountFullName) {
						$message = lang('financial_account.verify_account_name');
						$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
						$verify_result['error_message'] = $message;
						$verify_result['passed'] = false;
					}
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyAvailableBankTypeOnDeposit($bank_type_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(array('banktype'));
			$is_available = $this->banktype->isEnabledDeposit($bank_type_id);
			if(!$is_available) {
				$message = sprintf(lang('gen.error.forbidden'), lang('cashier.81'));
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyAvailableBankTypeOnWithdrawal($bank_type_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(array('banktype'));
			$is_available = $this->banktype->isEnabledWithdrawal($bank_type_id);
			if(!$is_available) {
				$message = sprintf(lang('gen.error.forbidden'), lang('cashier.81'));
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	// Not really a verify function. Just for assigning variable
	private function execAfterVerifyAvailableBankType($bank_type_id, &$bank_type, &$payment_type_flag) {
		$verify_result = ['passed' => true, 'error_message' => ''];

		$bank_type = $this->banktype->getBankTypeById($bank_type_id);
		$payment_type_flag = $bank_type->payment_type_flag;
		return $verify_result;
	}

	private function verifyEmailAddressBelongToPlayer($playerId=null, $player_username=null, $email_address) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		if(empty($playerId) && empty($player_username)) {
			$message = lang('player not exist');
			$verify_result['error_code'] = self::CODE_PLAYER_NOT_FOUND;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
			return $verify_result;
		}
		$player = null;
		if(!empty($playerId)) {
			$player = $this->player_model->getPlayerById($playerId);
		}
		else if(!empty($player_username)) {
			$player = $this->player_model->getPlayerByUsername($player_username);
		}
		$this->utils->debug_log('=========================verifyEmailAddressBelongToPlayer'.__METHOD__.' compaere $player->email and $email_address', $player->email, $email_address);
		if ($player->email != $email_address){
			$message = lang('Input email address does not belong to the current player');
			$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
	}

	private function verifyPhoneNumberBelongToPlayer($playerId=null, $player_username=null, $phone_number) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		if(empty($playerId) && empty($player_username)) {
			$message = lang('player not exist');
			$message = lang('Input email address does not belong to the current player');
			$verify_result['error_code'] = self::CODE_PLAYER_NOT_FOUND;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
			return $verify_result;
		}
		if(!empty($playerId)) {
			$player_phone_number = $this->player_model->getPlayerContactNumber($playerId);
		}
		else if(!empty($player_username)) {
			$player_phone_number = $this->player_model->getPlayerContactNumberByUsername($player_username);
		}

		if ($player_phone_number != $phone_number){
			$message = lang('Input phone number does not belong to the current player');
			$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
	}

	private function verifySendEmailCoolDownPeriod($playerId) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			// OGP-23086: check if still in cool down period of last verify email
			$time_last_vemail_redis_key = "time_last_verification_email_{$playerId}";
			$time_last_vemail = intval($this->utils->readRedis($time_last_vemail_redis_key));
			$verify_email_cd_interval = intval($this->utils->getConfig('verification_email_cooldown_time_sec'));
			$this->utils->debug_log(__METHOD__, [ 'time_last_vemail' => $time_last_vemail, 'verify_email_cd_interval' => $verify_email_cd_interval, 'time_passed' => (time() - $time_last_vemail) ]);
			if ($time_last_vemail > 0 && (time() - $time_last_vemail) < $verify_email_cd_interval) {
				$message = lang('Still in cool down period of last verification email, please try later');
				// $this->utils->debug_log(__METHOD__, 'still in cd period', $message);
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;

			}
			$this->utils->writeRedis($time_last_vemail_redis_key, time());
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyEmailTemplateEnabled($playerId, $email, $template_name, $template_params, &$template) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->library(['email_manager']);
			$template = $this->email_manager->template('player', $template_name, $template_params);
			$template_enabled = $template->getIsEnableByTemplateName(true);
			if (!$template_enabled['enable']) {
				$this->email_verification->recordReport($playerId, $email, $template_name, null, null, email_verification::SENDING_STATUS_FAILED);
				$message = lang('Security') . ' - ' . lang('Email Verification') .'<br>'. $template_enabled['message'];
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyDuplicatePhoneNumber($playerId, $contact_number, $forceBind = false) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if (!$this->utils->isEnabledFeature('allow_player_same_number')) {
				$this->load->model(['player_model']);
				$diff_contactNumber = true;
				if(!empty($playerId)) {
					$origin = $this->player_model->getPlayerInfoById($playerId);
					$diff_contactNumber = ($contact_number !== $origin['contactNumber']);
				}
				if (!empty($contact_number) && $diff_contactNumber) {
					$result = !$this->player_model->checkContactExist($contact_number);
					if (!$result) {
						$message = lang('The contact number has been used');
						$verify_result['error_code'] = self::CODE_DUPLICATED_PHONE_NUMBER;
						$verify_result['error_message'] = $message;
						$verify_result['passed'] = false;
					}
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyDuplicateCPFNumber($playerId, $cpfNumber) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			$origin = $this->player_model->getPlayerInfoById($playerId);
			$diff_pixNumber = ($cpfNumber !== $origin['pix_number']);

			if (!empty($cpfNumber) && $diff_pixNumber) {
				$result = !$this->player_model->checkCpfNumberExist($cpfNumber);
				if (!$result) {
					$message = lang('CPF number has been used');
					$verify_result['error_code'] = self::CODE_DUPLICATED_CPF_NUMBER;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyDuplicateEmail($playerId, $email) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			$origin = $this->player_model->getPlayerInfoById($playerId);
			$diff_email = ($email !== $origin['email']);

			if (!empty($email) && $diff_email) {
				$result = !$this->player_model->checkEmailExist($email);

				if (!$result) {
					$message = lang('check_email'). ': '. $email . " " . lang('notify.5');
					$verify_result['error_code'] = self::CODE_PLAYER_EMAIL_ALREADY_EXISTS;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyIPLimit() {

		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
		//check ip limit
		$ip=$this->utils->getIP();
		$type='register';
		$this->load->model(['player_model']);
		$err=null;
		$reached=$this->player_model->reachedIpLimitHourlyBy($ip, $type, $err);
		if($reached===null){
			$message = $err;
			$verify_result['error_code'] = self::CODE_IP_RESTRICTED;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		if($reached===true){
			$this->utils->error_log('reached ip limit, blocked', $ip, $type);
			$message = lang('Reached limit, try later');
			$verify_result['error_code'] = self::CODE_IP_RESTRICTED;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function isReachDailyIPAllowedRegistrationLimit() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			if($this->player_model->isReachDailyIPAllowedRegistrationLimit($this->utils->getIp())){
				$message = lang('reg.reach_limit_of_single_ip_registrations_per_day');
				$verify_result['error_code'] = self::CODE_IP_RESTRICTED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	protected function verifyPasswordLimit($password, $ignore_registration_settings = false) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$enable_form_validation_under_registration = $this->utils->getConfig('enable_form_validation_under_registration');
			$min_password_length = $this->utils->getConfig('default_min_size_password');
			$max_password_length = $this->utils->getConfig('default_max_size_password');

			// apply isPasswordMinMaxEnabled().
			$password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
			$min_password_length = !empty($password_min_max_enabled['min']) ? $password_min_max_enabled['min'] : $this->utils->getConfig('default_min_size_password');
			$max_password_length = !empty($password_min_max_enabled['max']) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');

			if( in_array('password',$enable_form_validation_under_registration)) {
				$regex_password = $this->utils->getPasswordReg();
				$message = '';
				if(strlen($password) < $min_password_length) {
					$message = sprintf(lang('formvalidation.min_length'), lang('Password'), $min_password_length);
					$verify_result['error_code'] = self::CODE_INVALID_PASSWORD;
					$verify_result['passed'] = false;
				}
				else if (strlen($password) > $max_password_length) {
					$message = sprintf(lang('formvalidation.max_length'), lang('Password'), $max_password_length);
					$verify_result['error_code'] = self::CODE_INVALID_PASSWORD;
					$verify_result['passed'] = false;
				}
				else if (!preg_match($regex_password, $password)) {
					$message = sprintf(lang('formvalidation.regex_match'), lang('Password'));
					$verify_result['error_code'] = self::CODE_INVALID_PASSWORD;
					$verify_result['passed'] = false;
				}
				$verify_result['error_message'] = $message;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyPasswordExcludeFields($post_data) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = '';
			$password = $post_data['password'];
			$playerValidate = $this->utils->getConfig('player_validator');

			if (isset($playerValidate['password']['excludeFields'])) {
				$excludeFields = $playerValidate['password']['excludeFields'];
				foreach ($excludeFields as $field) {
					$value = isset($post_data[$field]) ? $post_data[$field] : '';
					if (!empty($value)) {

						if ($field == 'birthdate') {
							$value = str_replace('-', '', $value);
						}

						if (stripos($password, $value) !== false) {
							$message = lang('Password cannot include') . ' ' . $field;
							$verify_result['error_code'] = self::CODE_INVALID_PASSWORD;
							$verify_result['passed'] = false;
						}
					}
				}
			}
			$verify_result['error_message'] = $message;
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyUsernameLimit($username) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$enable_form_validation_under_registration = $this->utils->getConfig('enable_form_validation_under_registration');
			$min_username_length = $this->utils->getConfig('default_min_size_username');
			$max_username_length = $this->utils->getConfig('default_max_size_username');
			$message = '';
			if( in_array('username',$enable_form_validation_under_registration)) {
				$usernameRegDetails = [];
				$regex_username = $this->utils->getUsernameReg($usernameRegDetails);
				if(strlen($username) < $min_username_length) {
					$message = sprintf(lang('formvalidation.min_length'), lang('Username'), $min_username_length);
					$verify_result['error_code'] = self::CODE_MALFORMED_PARAMETER;
					$verify_result['passed'] = false;
				}
				else if (strlen($username) > $max_username_length) {
					$message = sprintf(lang('formvalidation.max_length'), lang('Username'), $max_username_length);
					$verify_result['error_code'] = self::CODE_MALFORMED_PARAMETER;
					$verify_result['passed'] = false;
				}else if (!preg_match($regex_username, $username)) {
					$message = sprintf(lang('formvalidation.regex_match'), lang('Username'));
					$verify_result['error_code'] = self::CODE_MALFORMED_PARAMETER;
					$verify_result['passed'] = false;
				}
				$verify_result['error_message'] = $message;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyUsernameExist($username) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			$result = !$this->player_model->checkUsernameExist($username);
			if (!$result) {
				$message = lang('check_username'). ': ' . $username . " " . lang('notify.3');
				$verify_result['error_code'] = self::CODE_USERNAME_ALREADY_EXISTS;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyUsernameNotExist($username) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			$result = $this->player_model->checkUsernameExist($username);
			if (!$result) {
				$message = lang('check_username'). ': ' . $username . " " . lang('notify.68');
				$verify_result['error_code'] = self::CODE_PLAYER_NOT_FOUND;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyCPFNumberExist($cpfNumber) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			$result = !$this->player_model->checkCpfNumberExist($cpfNumber);
			if (!$result) {
				$message = lang('CPF number has been used');
				$verify_result['error_code'] = self::CODE_DUPLICATED_CPF_NUMBER;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyEmailExist($email) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['registration_setting']);
			$enable_form_validation_under_registration = $this->utils->getConfig('enable_form_validation_under_registration');
			if( in_array('email',$enable_form_validation_under_registration)) {
				// if ($this->registration_setting->isRegistrationFieldRequired('Email')) {
					$this->load->model(['player_model']);
					if(!empty($email) || $this->isRegistrationFieldRequired('Email')){

						$result = !$this->player_model->checkEmailExist($email);
						if (!$result) {
							$message = lang('check_email') . ': '. $email . " " . lang('notify.5');
							$verify_result['error_code'] = self::CODE_PLAYER_EMAIL_ALREADY_EXISTS;
							$verify_result['error_message'] = $message;
							$verify_result['passed'] = false;
						}
					}
				// }
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyIPAccessDailyTimes() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			if($this->player_model->isReachDailyIPAllowedRegistrationLimit($this->utils->getIp())){
				$message = lang('reg.reach_limit_of_single_ip_registrations_per_day');
				$verify_result['error_code'] = self::CODE_IP_RESTRICTED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyPasswordIsSameAsUsername($username, $password) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if (trim($username) == trim($password)) {
				$message = lang('validation.contentPassword02');
				$verify_result['error_code'] = self::CODE_INVALID_PASSWORD;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());

			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyContactNumberRegistration($contact_number){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			// form.validation.required
			$enable_form_validation_under_registration = $this->utils->getConfig('enable_form_validation_under_registration');
			if( !in_array('contactNumber',$enable_form_validation_under_registration)) {
				return $verify_result;
			}
			$required = $this->isRegistrationFieldRequired('Contact Number');
			if(empty($contact_number) && $required){
				// if(empty($contact_number) && $this->registration_setting->isRegistrationFieldRequired('Contact Number')){
				$message = lang('form.validation.required');
				$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
				$verify_result['error_message'] = 'Check Contact Number: '. sprintf(lang('form.validation.required'), lang('Contact Number'));
				$verify_result['passed'] = false;
				return $verify_result;
			}
			if(!empty($contact_number)){

				if (!preg_match('/^[0-9]+$/', $contact_number)) {
					$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
					$verify_result['error_message'] = 'Check Contact Number: '. sprintf(lang('form.validation.regex_match'), lang('Contact Number'));
					$verify_result['passed'] = false;
					return $verify_result;
				}

				//phone is exist
				$result = !$this->player_model->checkContactExist($contact_number);
				if (!$result) {
					$message = lang('The contact number has been used');
					$verify_result['error_code'] = self::CODE_DUPLICATED_PHONE_NUMBER;
					$verify_result['error_message'] =  'Check Contact Number: '.$message;
					$verify_result['passed'] = false;
					return $verify_result;
				}

				$lengthOfcontactNumber = strlen($contact_number);
				$playerValidate = $this->utils->getConfig('player_validator');
				$contactRule = isset($playerValidate['contact_number']) ? $playerValidate['contact_number'] : [];
				$contactMin  = isset($contactRule['min']) ? $contactRule['min'] : "";
				$contactMax  = isset($contactRule['max']) ? $contactRule['max'] : "";

				if (isset($contactMin, $contactMax) && $contactMin == $contactMax && $lengthOfcontactNumber != intval($contactMin)) {
						$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
						$verify_result['error_message'] = 'Check Contact Number: '. sprintf(lang('form.validation.exact_length'), lang('Contact Number'), $contactMax);
						$verify_result['passed'] = false;

				} else {
					if (!empty($contactMin) && $lengthOfcontactNumber < $contactMin) {
						$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
						$verify_result['error_message'] = 'Check Contact Number: '. sprintf(lang('form.validation.min_length'), lang('Contact Number'), $contactMin);
						$verify_result['passed'] = false;

					}
					if (!empty($contactMax) && $lengthOfcontactNumber > $contactMax) {
						$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
						$verify_result['error_message'] = 'Check Contact Number: '. sprintf(lang('form.validation.max_length'), lang('Contact Number'), $contactMax);
						$verify_result['passed'] = false;
					}
				}
			}


			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyOTPCodeRegistration($otp_source, $player_id, $contact_number, $otp_verification_code){
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			$isRegistrationFieldRequired = $this->isRegistrationFieldRequired('SMS Verification Code');
			if (empty($otp_verification_code) && $isRegistrationFieldRequired) {
				$message = sprintf(lang("gen.error.required"), lang("SMS Verification Code"));
				$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
				return $verify_result;
			}
			if(!empty($otp_verification_code)){
				$verify_result = $this->checkOTPCodeWithSource($otp_source, $player_id, $contact_number, $otp_verification_code);
				if(!$verify_result['success']){
					$verify_result['passed'] = false;
					$verify_result['error_code'] = $verify_result['errorCode'];
					$verify_result['error_message'] = $verify_result['errorMessage'];
					return $verify_result;
				}
				$verify_result['passed'] = true;
			}
			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyOTPCodeAddBank($playerId, $otpCode, $otpSource = self::OTP_SOURCE_SMS) {
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			if(!empty($otpCode)){
				$player_contact = $this->player->getPlayerContactInfo($playerId);
				$verify_result = $this->checkOTPCodeWithSource($otpSource, $playerId, $player_contact['contactNumber'], $otpCode);
				if(!$verify_result['success']){
					$verify_result['passed'] = false;
					$verify_result['error_code'] = $verify_result['errorCode'];
					$verify_result['error_message'] = $verify_result['errorMessage'];
					return $verify_result;
				}
				$verify_result['passed'] = true;
			}
			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function checkVerifiedCpfNumber($playerId, $cpfNumber){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$verified = false;
			$pixSystemInfo = $this->utils->getConfig('pix_system_info');
			if($pixSystemInfo['identify_cpf_numer_on_kyc']['enabled']){
				$origin = $this->player_model->getPlayerInfoById($playerId);
				$diff_pixNumber = ($cpfNumber !== $origin['pix_number']);
				if($diff_pixNumber){
					$verified = $this->verifyCpfStatusOnKyc($playerId);
				}
			}

			if($verified){
				$message = sprintf(lang('reg.not_allowed_edit_for_already_verified'), lang('cpfNumber'));
				$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function checkVerifiedPhoneNumber($playerId, $contact_number){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$origin = $this->player_model->getPlayerInfoById($playerId);
			$diff_contactNumber = ($contact_number !== $origin['contactNumber']);

			if ( $diff_contactNumber) {
				$player = $this->player_functions->getPlayerById($playerId);
				$verified = empty($player['verified_phone'])? false: true;
				if( ! empty($origin['contactNumber']) && $verified ){
					$message = sprintf(lang('reg.not_allowed_edit_for_already_verified'), lang('phoneNumber'));
					$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function checkVerifiedEmail($playerId, $email){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$origin = $this->player_model->getPlayerInfoById($playerId);
			$diff_email = ($email !== $origin['email']);

			if ($diff_email) {
				$player = $this->player_functions->getPlayerById($playerId);
				$verified = $player['verified_email'];
				if($verified){
					$message = sprintf(lang('reg.not_allowed_edit_for_already_verified'), lang('verified.email')!=null?lang('verified.email'):lang('Email'));
					$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	/**
	 * checkPlayerPhoneNumberVerified function
	 * to check if player phone number is verified
	 * @param int $playerId
	 * @param boolean $forceBind
	 * @return array
	 */
	protected function checkPlayerPhoneNumberVerified($playerId, $forceBind = false){

		$verify_result = ['passed' => true, 'error_message' => ''];
		try {

			$disable_check_unverified_email = $this->utils->getConfig('disable_check_unverified_email');
			if($disable_check_unverified_email){
				return $verify_result;
			}

			$player = $this->player_functions->getPlayerById($playerId);
			$verified = empty($player['verified_phone'])? false: true;
			if(!$verified && !$forceBind){
				$message = lang('overview.mobile.not.verify');
				$verify_result['error_code'] = self::CODE_PHONENUMBER_NOT_VERIFIED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;

		}
	}
	/**
	 * checkPlayerEmailVerified function function
	 *	to check if player email is verified
	 * @param int $playerId
	 * @param boolean $forceBind
	 * @return array
	 */
	protected function checkPlayerEmailVerified($playerId, $forceBind = false){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {

			$disable_check_unverified_email = $this->utils->getConfig('disable_check_unverified_email');
			if($disable_check_unverified_email){
				return $verify_result;
			}

			$player = $this->player_functions->getPlayerById($playerId);
			$verified = $player['verified_email'];
			if(!$verified && !$forceBind){
				$message = lang('reg.rule_email_not_verified');
				$verify_result['error_code'] = self::CODE_EMAIL_NOT_VERIFIED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;

		}
	}

	private function verifyEditableInAccountSetting($playerId, $profileData){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$not_allowed_list = [];
            $this->load->model(['player_model']);
            $player = $this->player_model->getPlayerInfoById($playerId);
			if(!$this->utils->getConfig('temp_disable_sbe_acc_setting')){
				foreach ($profileData as $fieldName => $value){
                    $diff = ($player[$fieldName] !== $value);
                    if(!$diff){
                        continue;
                    }
                    
                    $result = true;
					if(!empty($value)){
						$result = $this->registration_setting->checkAccountInfoFieldAllowEdit($player, $fieldName);
						if(!$result){
							$not_allowed_list[] = $this->playerapi_lib->matchOutputProfileColumnName($fieldName);
						}
					}
				}
			}

			if(!empty($not_allowed_list)){
				$not_allowed_list = implode(",", $not_allowed_list);
				$message = sprintf(lang('reg.not_allowed_edit_for_acc_setting'), $not_allowed_list);
				$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}

			return $verify_result;

		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyFieldOnlyEditedOnce($playerId, $profileData){
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model']);
			$not_allowed_list = [];
			$origin = $this->player_model->getPlayerInfoById($playerId);
			$edited_only_once_in_player = $this->utils->getConfig('only_edited_once_in_player');
			foreach ($edited_only_once_in_player as $fieldName) {
				if(!empty($origin[$fieldName]) && !empty($profileData[$fieldName])){
					$diff = ($profileData[$fieldName] !== $origin[$fieldName]);
					if($diff){
						$not_allowed_list[] = $this->playerapi_lib->matchOutputProfileColumnName($fieldName);
					}
				}
			}

			if(!empty($not_allowed_list)){
				$not_allowed_list = implode(",", $not_allowed_list);
				$message = sprintf(lang('reg.not_allowed_edit_for_only_edited_once'), $not_allowed_list);
				$verify_result['error_code'] = self::CODE_INVALID_PARAMETER;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}

			return $verify_result;

		}catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyEnableSMSAPI() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if ($this->utils->getConfig('disabled_sms')) {
				$message = lang('Disabled SMS');
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyBlockPlayerIp() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if(!$this->checkBlockPlayerIPOnly(true)){
				$message = lang('Player is blocked');
				$verify_result['error_code'] = self::CODE_PLAYER_BLOCKED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifySessionId($session_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		if(!$session_id) {
			$message = lang('Session id invalid');
			$verify_result['error_code'] = self::CODE_SERVER_ERROR;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
	}

	private function verifySendSMSCoolDownPeriod($sms_cooldown_Time, $phone_number, $last_sms_time) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['sms_verification']);
			# Check the send count with ip or mobile on cooldown period
			if($this->sms_verification->checkIPAndMobileLastTIme($sms_cooldown_Time, $phone_number)) {
				$message = sprintf(lang('It is not allowed to send twice within %s seconds if same IP/Phone no, please try again later.'), $sms_cooldown_Time);
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
				return $verify_result;
			}
			# This check ensures for a given session (i.e. session ID), SMS cannot be sent again within the cooldown period
			if($last_sms_time && time() - $last_sms_time <= $sms_cooldown_Time) {
				$message = lang('You are sending SMS too frequently. Please wait.');
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
				return $verify_result;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyExceedMaxGenerateCountPerMinute() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['sms_verification']);
			$codeCount = $this->sms_verification->getVerificationCodeCountPastMinute();
			if($codeCount > $this->config->item('sms_global_max_per_minute')) {
				$this->utils->error_log("Sent [$codeCount] SMS in the past minute, exceeded config max [".$this->config->item('sms_global_max_per_minute')."]");
				$message = lang('SMS process is currently busy. Please wait.');
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyExceedMaxPlayerSMSCountPerDay($phone_number, $numCount) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['sms_verification']);
			if($numCount >= $this->config->item('sms_max_per_num_per_day')) {
				$this->utils->error_log("Sent maximum [$numCount] SMS to this number today.");
				$message = sprintf(lang('One username is only allowed to send %s texts per day, please try again tomorrow.'), $this->config->item('sms_max_per_num_per_day'));
				$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyExceedRestrictAreaSendCount($player_id, $restrictArea) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['sms_verification']);
			$restrictSmsSendNum = (int) $this->utils->getConfig('sms_restrict_send_num');
			$enableRestrictSmsSendNum = $this->utils->isEnabledFeature('enable_restrict_sms_send_num_in_player_center_phone_verification');
			$isRestrictArea = $this->sms_verification->isRestrictArea($restrictArea);
			if ($isRestrictArea && $player_id && $enableRestrictSmsSendNum && $restrictSmsSendNum > 0) {
				$numDaily = $this->sms_verification->sendNumDaily($player_id, $restrictArea);
				if ($numDaily >= $restrictSmsSendNum) {
					$message = lang('Today has exceeded the sending limit');
					$verify_result['error_code'] = self::CODE_OPERATION_FAILED;
					$verify_result['error_message'] = $message;
					$verify_result['passed'] = false;
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyPlayerPassword($player_id, $password) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$check_password_result = $this->player_library->isValidPassword($player_id, $password);	// true or false
			if(!$check_password_result) {
				// if password is incorrect
				//$message = "Your Password is incorrect. New Password cannot be save";
				$message = lang('notify.26');
				$verify_result['error_code'] = self::CODE_INVALID_PASSWORD;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyEnableUploadAvatarFile() {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if($this->utils->isEnabledFeature('hidden_avater_upload')){
				$message = lang('upload player avater is disabled by backend setup');
				$verify_result['error_code'] = self::CODE_AVATAR_UPLOAD_FAILED;
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function allowedCountryCode(){
		$code_list =$this->playerapi_lib->getCountryCodeList();
		return !empty($code_list) ? array_values($code_list) : [];
	}

	private function allowedCountryPhoneCode($countryCode = null){
		$code_list = $this->playerapi_lib->getCountryPhoneCodeList($countryCode);
		return !empty($code_list) ? array_values($code_list) : [];
	}

	private function allowedCountryIsoLang(){
		$code_list = $this->playerapi_lib->getIsoLangCountry();
		return !empty($code_list) ? array_values($code_list) : [];
	}

	public function checkBankTypeAddable($bankType){
		$addable = true;
		$falg = $this->playerapi_lib->matchOutputPaymentTypeFlag($bankType->payment_type_flag);
		switch ($falg) {
			case 'PIX':
				$addable = $this->getAddRuleForPix($bankType);
				break;
		}
		return $addable;
	}

	public function checkPlayerBankEditable($bankDetailId){
		$editable = (bool)$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_delete');
		$playerBankInfo = $this->playerbankdetails->getBankDetailInfo($bankDetailId);
		$bankType = $this->playerapi_lib->matchOutputPaymentTypeFlag($playerBankInfo['payment_type_flag']);
		switch ($bankType) {
			case 'PIX':
				$editable = $this->getEditRuleForPix($playerBankInfo);
				break;
		}
		return $editable;
	}

	private function getAddRuleForPix($bankType){
		$wrapBankInfo['bank_code'] = $bankType->bank_code;
		//there is an editable version for temporary use.
		$pixAddable = $this->getEditRuleForPix($wrapBankInfo);
		return $pixAddable;
	}

	private function getEditRuleForPix($playerBankInfo){
		$currentPixtype = $playerBankInfo['bank_code'];
		$pixSystemInfo  = $this->utils->getConfig('pix_system_info');
		$pixEditable	= $pixSystemInfo['edit_pix_account']['enabled'];
		$allowEditType  = $pixSystemInfo['edit_pix_account']['allow_type'];
		if(!$pixEditable){
			return $pixEditable;
		}else{
			$pixEditable = false;
			if (in_array($currentPixtype, $allowEditType)) {
			    $pixEditable = true;
			}
			return $pixEditable;
		}
	}

	protected function autoBuildPlayerPixAccount($playerId){
		$bankDetailsIds = $this->playerbankdetails->autoBuildPlayerPixAccount($playerId);
		return $bankDetailsIds;
    }

    private function verifyPasswordNotEmpty($password) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
            if(empty($password)){
                $verify_result['error_code'] = self::CODE_PASSWORD_NEED_TO_RESEST;
                $verify_result['error_message'] = $this->codes[self::CODE_PASSWORD_NEED_TO_RESEST];
                $verify_result['passed'] = false;
            }
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}
    
	public function verifyCpfStatusOnKyc($playerId){
		$this->load->model(array('kyc_status_model'));
		$verified = $this->kyc_status_model->player_valid_documents($playerId);
		return $verified;
	}
}