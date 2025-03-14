<?php

trait player_withdrawal_utils_module {

	private function verifyAgencyCreditMode($player) {
		// $this->utils->debug_log('============verifyAgencyCreditMode start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(['player_model', 'wallet_model']);

			if ($this->utils->isEnabledFeature('agent_player_cannot_use_deposit_withdraw') && $player && $player['credit_mode']) {
				if ($this->wallet_model->isPlayerWalletAccountZero($playerId)) {
					$this->utils->debug_log('============'. __METHOD__ ." disabledCreditMode");
					$this->player_model->disabledCreditMode($playerId);
				} else {
					$message = lang('credit mode is on, deposit/withdraw is not allowed');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyBankTypeExist($type) {
		// $this->utils->debug_log('============verifyExistDeposiOrder start');
		$verify_result = ['passed' => true, 'error_message' => ''];

		if (!in_array($type, self::BANK_TYPE_ARR)) {
			$message = lang('withdrawal_bank_type_not_exist');
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
		}
		return $verify_result;
	}

	private function verifyEnablePlayerWithdrawal($player) {
		// $this->utils->debug_log('============verifyEnablePlayerWithdrawal start');
		$verify_result = ['passed' => true, 'error_message' => ''];

		if ($player['enabled_withdrawal'] == self::WITHDRAWAL_DISABLED) {
			$message = lang('withdrawal_disabled_message');
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
		}
		return $verify_result;
	}

    private function verifyPlayerCompleteUserinfo($playerId) {
        
		$verify_result = ['passed' => true, 'error_message' => ''];
        $enabledFeature = $this->operatorglobalsettings->getSettingValueWithoutCache("financial_account_complete_required_userinfo_before_withdrawal");
		if ($enabledFeature) {
            // from player_account_utils_module
            $progress = $this->getProfileProgress($playerId);
            if ($progress < 100) {
                $message = lang('withdrawal_disabled_message.profile_incomplete');
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
            }
        }
		return $verify_result;
	}

	private function verifyKycRiskWithdrawalStatus($playerId) {
		// $this->utils->debug_log('============verifyKycRiskWithdrawalStatus start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model('risk_score_model');
			if ($this->utils->isEnabledFeature("show_allowed_withdrawal_status") && $this->utils->isEnabledFeature("show_risk_score") && $this->utils->isEnabledFeature("show_kyc_status")) {
				if(!$this->risk_score_model->generate_allowed_withdrawal_status($playerId)){
					$message = lang('not_allowed_kyc_risk_score_message');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifySubwalletNegativeBalance($playerId) {
		// $this->utils->debug_log('============verifySubwalletNegativeBalance start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if($this->utils->getConfig('check_sub_wallect_balance_in_withdrawal')){
				$balanceDetails = $this->wallet_model->getBalanceDetails($playerId);
				if(is_array($balanceDetails) && !empty($balanceDetails)){
					foreach ($balanceDetails['sub_wallet'] as $subWallet) {
						if($subWallet['totalBalanceAmount'] < 0){
							$message = sprintf(lang('SubWallet with negative balance'),$subWallet['game'],$subWallet['totalBalanceAmount']);
							$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
							return $verify_result;
						}
					}
				} else {
					$message = lang('check subwallet balance failed');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyExistBankDetailsId($bankDetailsId) {
		// $this->utils->debug_log('============verifyExistBankDetailsId start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		// $bankDetailsId = null;
		if(empty($bankDetailsId)) {
			$this->utils->debug_log('============'. __METHOD__ ." bankDetailsId", $bankDetailsId);
			$message = lang('notify.verify_player_bank_details_empty');
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
		}
		return $verify_result;
	}

	private function verifyPlayerExistRealName($playerId) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$withdrawal_name_required_before_withdrawal = $this->utils->getConfig('withdrawal_name_required_before_withdrawal');
			if($withdrawal_name_required_before_withdrawal){
				$playerInfo = $this->player_model->getPlayerDetailsById($playerId);
				$fullName = trim("{$playerInfo->lastName} {$playerInfo->firstName}");
				if(empty($fullName)){
					$message = lang('notify.verify_player_real_name_empty');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		}catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyWithdrawalPassword($playerId, $withdrawal_password) {
		// $this->utils->debug_log('============verifyExistBankDetailsId start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			# SMS and Password verification is not yet present in new player center. Verify withdraw password only
			$withdrawVerificationMethod = $this->utils->getConfig('withdraw_verification');
			if($withdrawVerificationMethod == 'withdrawal_password'){
				$checkWithdrawPassword=$this->player_model->validateWithdrawalPassword($playerId, $withdrawal_password);
				if(!$checkWithdrawPassword) {
					$message = lang('Invalid Withdrawal Password');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyAmountDecimal($amount) {
		// $this->utils->debug_log('============verifyAmountDecimal start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		if($this->CI->config->item('disable_withdraw_amount_is_decimal')){
			if (is_numeric( $amount ) && floor( $amount ) != $amount) {
				$message = lang('notify.118');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
		}
		return $verify_result;
	}

	private function verifyBankDetailsBelongToPlayer($playerId, $bankDetailsId) {
		// $this->utils->debug_log('============verifyBankDetailsBelongToPlayer start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			// Verify if the submittng bankDetailsId belong to thie player
			$check_if_bank_detail_belnog_to_player = false;
			$check_bank_detail_enabled_withdrawal_to_player = false;
			$available_banks = $this->playerbankdetails->getAvailableWithdrawBankDetail($playerId);
			$this->utils->debug_log('============'. __METHOD__ .'available_banks', $available_banks);
			if($available_banks == NULL) {
				$message = lang('notify.verify_player_bank_details_null');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}
			foreach ($available_banks as $each_bank) {
				if($each_bank['playerBankDetailsId'] == $bankDetailsId) {
					$check_if_bank_detail_belnog_to_player = true;
					if(1 === (int)$each_bank['enabled_withdrawal']){
						#enabled_withdrawal = 1 is enable
						$check_bank_detail_enabled_withdrawal_to_player = true;
						break;
					}
				}
			}

			if(!$check_if_bank_detail_belnog_to_player) {
				$this->utils->debug_log('============'. __METHOD__ .' Verify player bank details error. The bankDetailsId: [$bankDetailsId] is not belong to the playerId: [$playerId].');
				$message = lang('notify.verify_player_bank_details_binding_error');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}

			if(!$check_bank_detail_enabled_withdrawal_to_player) {
				$this->utils->debug_log('============'. __METHOD__ .' Financial Institution bank account is disable');
				$message = lang('notify.verify_player_Financial_account_enable');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyPlayerWithdrawalRule($playerId, $amount) {
		// $this->utils->debug_log('============verifyBankDetailsBelongToPlayer start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			## Check whether withdrawal satisfies withdrawal rule for this player
			$playerWithdrawalRule = $this->utils->getWithdrawMinMax($playerId);
			list($withdrawalProcessingCount, $processingAmount) = $this->wallet_model->countTodayProcessingWithdraw($playerId);
			list($withdrawalPaidCount, $paidAmount) = $this->transactions->count_today_withdraw($playerId);
			$amount_used = $processingAmount + $paidAmount;
			$time_used = $withdrawalProcessingCount + $withdrawalPaidCount;

			if (!$playerWithdrawalRule || is_null($playerWithdrawalRule['max_withdraw_per_transaction'])) {
				$message = lang('Undefined withdraw rule');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			else if ($amount > $playerWithdrawalRule['max_withdraw_per_transaction']) {
				$message = lang('Max Withdrawal Per Transaction is').$playerWithdrawalRule['max_withdraw_per_transaction'];
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			if ($amount + $amount_used > $playerWithdrawalRule['daily_max_withdraw_amount']) {
				$message = lang('notify.56');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			if ($time_used >= $playerWithdrawalRule['withdraw_times_limit']) {
				$message = lang('notify.106');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}

			if ($amount < $playerWithdrawalRule['min_withdraw_per_transaction']) {
				$message = lang('notify.102') . $playerWithdrawalRule['min_withdraw_per_transaction'];
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyUnfinishedWithdrawCondition($playerId) {
		// $this->utils->debug_log('============verifyUnfinishedWithdrawCondition start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			#check  withdrawal conditions
			if ($this->utils->isEnabledFeature('check_withdrawal_conditions')) {
				$this->utils->debug_log('============'. __METHOD__ .' used feature: check_withdrawal_conditions');
				if(FALSE !== $un_finished = $this->withdraw_condition->getPlayerUnfinishedWithdrawCondition($playerId)){
					//un_finished_withdrawal
					$message = lang('notify.withdrawal.condition');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
				return $verify_result;
			}

			#check  withdrawal conditions -- for each
			if ($this->utils->isEnabledFeature('check_withdrawal_conditions_foreach')) {
				$this->utils->debug_log('============'. __METHOD__ .' used feature: check_withdrawal_conditions_foreach');
				if( FALSE !== $withdraw_data = $this->withdraw_condition->getPlayerUnfinishedWithdrawConditionForeach($playerId)){
					//un_finished
					$message = lang('notify.withdrawal.condition');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			    }
			    return $verify_result;
			}

			##check deposit conditions in withdrawal conditions -- for each
			if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')){
				$this->utils->debug_log('============'. __METHOD__ .' used feature: check_withdrawal_conditions_foreach');
				if(FALSE !== $un_finished_deposit = $this->withdraw_condition->getPlayerUnfinishedDepositConditionForeach($playerId)){
					//un_finished_deposit
					$message = lang('notify.withdrawal.condition');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
				return $verify_result;
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyLastWithdrawalRequestDone($playerId) {
		// $this->utils->debug_log('============verifyLastWithdrawalRequestDone start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			## Check whether multiple pending withdraws are allowed
			$this->load->model('group_level');
			if ($this->group_level->isOneWithdrawOnly($playerId)) {
				$this->load->model('player_model');
				if ($this->player_model->countWithdrawByStatusList($playerId) >= 1) {
					$message = lang('Sorry, your last withdraw request is not done, so you can\'t start new request');;
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyEmptybankTypeId($bankDetailsId, $bank_type_id) {
		// $this->utils->debug_log('============verifyEmptybankTypeId start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		if(!$bankDetailsId) {
			if(empty($bank_type_id)) {
				$message = lang('Sorry, please specify receiving account information');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
		}
		return $verify_result;
	}

	private function verifyFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $amount, $withdrawFeeAmount) {
		// $this->utils->debug_log('============verifyFeeWhenWithdrawalAmountOverMonthlyAmount start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			#if enable config get withdrawFee from player
			if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
				$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);
				$this->utils->debug_log('============'. __METHOD__ , 'amount', $amount, 'withdrawFeeAmount', $withdrawFeeAmount, 'mainAmount', $mainAmount);
				if ($amount + $withdrawFeeAmount > $mainAmount) {
					$message = lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyWithdrawalFeeAmountOverMainAmount($playerId, $amount, $withdrawFeeAmount) {
		$this->utils->debug_log('============verifyWithdrawalFeeAmountOverMainAmount start playerId : '.$playerId);
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);
			if ($amount + $withdrawFeeAmount > $mainAmount) {
				$message = lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			}
			$this->utils->debug_log('============'. __METHOD__ , 'amount', $amount, 'withdrawFeeAmount', $withdrawFeeAmount, 'mainAmount', $mainAmount, 'verify_result', $verify_result);

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyWithdrawalBankFeeAmountOverMainAmount($playerId, $amount, $withdrawBankFeeAmount, $withdrawFeeAmount) {
		$this->utils->debug_log('============verifyWithdrawalBankFeeAmountOverMainAmount start playerId : '.$playerId);
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$mainAmount = 0;
			$checkFeeAmt = 0;
			$logs = '';
			if ($withdrawBankFeeAmount > 0) {
				$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);

				if ($withdrawFeeAmount > 0) {
					$checkFeeAmt = $amount + $withdrawBankFeeAmount + $withdrawFeeAmount;
					$logs = lang('Withdrawal Amount + Withdrawal fee + Withdrawal bank fee is greater than Current Balance');
				}else{
					$checkFeeAmt = $amount + $withdrawBankFeeAmount;
					$logs = lang('Withdrawal Amount + Withdrawal bank fee is greater than Current Balance');
				}

				if ($checkFeeAmt > $mainAmount) {
					$message = lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance');
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
			}

			$this->utils->debug_log('============'. __METHOD__ , 'amount', $amount, 'withdrawBankFeeAmount', $withdrawBankFeeAmount, 'withdrawFeeAmount', $withdrawFeeAmount, 'mainAmount', $mainAmount, 'checkFeeAmt', $checkFeeAmt, 'verify_result', $verify_result, 'logs', $logs);

			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	private function verifyHasSufficientBalance($hasSufficientBalance, $errorMsg) {
		// $this->utils->debug_log('============verifyFeeWhenWithdrawalAmountOverMonthlyAmount start');
		$verify_result = ['passed' => true, 'error_message' => ''];

		if(!$hasSufficientBalance) {
			$message = empty($errorMsg) ? lang('notify.55') : $errorMsg;
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
		}
		return $verify_result;
	}

	private function addNewBankDetails($bankDetailsId, $playerId, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch) {
		try {
			## Save bank info if it's new (no bankDetailsId given)
			if(!$bankDetailsId) {
				$data = array(
					'playerId' => $playerId,
					'bankTypeId' => $bank_type_id,
					'bankAccountNumber' => $bank_account_number,
					'bankAccountFullName' => $bank_account_full_name,
					'bankAddress' => '',
					'province' => '',
					'city' => '',
					'branch' => $bank_branch,
					'isRemember' => Playerbankdetails::REMEMBERED,
					'dwBank' => Playerbankdetails::WITHDRAWAL_BANK,
					'verified' => '1',
					'status' => Playerbankdetails::STATUS_ACTIVE,
					'phone' => $this->input->post('phone'),
				);

				if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_default_unverified')){
					$data['verified'] = '0';
				}

				$this->load->library('player_functions');
				$bankDetailsId = $this->player_functions->addBankDetailsByWithdrawal($data);
			}
			return $bankDetailsId;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
		}
	}

	private function buildInitwalletAccountDataArray($walletAccountData, $playerId, $bankDetailsId, $player, $amount, $withdrawFeeAmount, $withdrawBankFeeAmount) {
		try {
			$playerAccount = $this->player_model->getPlayerAccountByPlayerIdOnly($playerId);
			$dwIp = $this->utils->getIP();
			$geolocation = $this->utils->getGeoplugin($dwIp);

			# Get bank details
			$playerBankDetails = $this->playerbankdetails->getBankList(array('playerBankDetailsId' => $bankDetailsId))[0];

			$banktype = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId']);
			$bankName = $banktype->bankName;

			//wallet account details
			$walletAccountData = array(
				'playerAccountId' => $playerAccount['playerAccountId'],
				'walletType' => 'Main',
				'dwMethod' => 1,
				'dwStatus' => 'request',
				'dwDateTime' => $this->utils->getNowForMysql(),
				'transactionType' => 'withdrawal',
				'dwIp' => $dwIp,
				'dwLocation' => $geolocation['geoplugin_city'] . ',' . $geolocation['geoplugin_countryName'],
				'transactionCode' => $this->wallet_model->getRandomTransactionCode(),
				'status' => '0',
				'playerId' => $playerId,
				'player_bank_details_id'=>$bankDetailsId,
				'bankAccountFullName' => $playerBankDetails['bankAccountFullName'],
				'bankAccountNumber' => $playerBankDetails['bankAccountNumber'],
				'bankName' => $bankName,
				'bankAddress' => $playerBankDetails['bankAddress'],
				'bankCity' => $playerBankDetails['city'],
				'bankProvince' => $playerBankDetails['province'],
				'bankBranch' => $playerBankDetails['branch'],
				'withdrawal_fee_amount'	 => $withdrawFeeAmount,
				'withdrawal_bank_fee' => $withdrawBankFeeAmount
			);

			return $walletAccountData;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
		}
	}

	private function resetDwStatusPendingReivew($walletAccountData, $playerId) {
		# OGP-3531
		if($this->utils->isEnabledFeature('enable_withdrawal_pending_review')){
			if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId)){
				$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
			}
		}
		return $walletAccountData;
	}

	private function resetDwStatusPendingReivewRiskScore($walletAccountData, $playerId) {
		if(
			$this->utils->isEnabledFeature('enable_withdrawal_pending_review') &&
			$this->utils->isEnabledFeature('enable_withdrawal_pending_review_in_risk_score') &&
			$this->utils->isEnabledFeature('show_risk_score')
		) {
			$risk_score_levels = $this->risk_score_model->getRiskScoreInfo(risk_score_model::RC);
			$this->utils->debug_log("risk_score_levels enable_withdrawal_pending_review_in_risk_score", "enabled");
			if(!empty($risk_score_levels) &&  isset($risk_score_levels['rules']) ){
				$this->utils->debug_log("risk_score_levels is not empty and risk_score_levels rules is set", true);
				$rules = json_decode($risk_score_levels['rules'],true);

				if(!empty($rules)) {
					$this->utils->debug_log("risk_score_levels rules not empty", $rules);
					foreach ($rules as $key => $value) {
						if(isset($value['withdrawal_pending_review'])){
							if($value['withdrawal_pending_review']){
								$player_risk_score_level = $this->risk_score_model->getPlayerCurrentRiskLevel($playerId);
								$this->utils->debug_log("player_risk_score_level", $player_risk_score_level);
								$this->utils->debug_log("risk_score value", $value['risk_score']);

								if($player_risk_score_level == $value['risk_score']){
									$this->utils->debug_log("risk_score_levels withdrawal_pending_review set", true);
									$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
								}
							}
						}
					}
				}
			}
		}
		$this->utils->debug_log("risk_score_levels walletAccountData status", $walletAccountData['dwStatus']);
		return $walletAccountData;
	}

	private function resetDwStatusPendingReivewCustom($walletAccountData, $playerId) {
		if($this->utils->getConfig('enable_pending_review_custom')){
			$getWithdrawalCustomSetting = json_decode($this->operatorglobalsettings->getSetting('custom_withdrawal_processing_stages')->template,true);

			if(!empty($getWithdrawalCustomSetting['pendingCustom']) && $getWithdrawalCustomSetting['pendingCustom']['enabled']){
				if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId)){
					$this->utils->debug_log("check player tag under pending custom walletAccountData", $walletAccountData['dwStatus']);
					$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
				}
			}
		}
		return $walletAccountData;
	}

	private function processAutomationRiskPreChecker($walletAccountId, $playerId) {
		if( ! empty( $this->config->item('enable_withdrawalARP') ) ){
			if( ! empty($walletAccountId) ){
				// $transactionCode = $walletAccountData['transactionCode']; // ex, W063808502389
				// $transactionCode = $this->wallet_model->getRequestSecureId($walletAccountId);
				// $walletAccountDeatil = $this->wallet_model->getWalletAccountByTransactionCode($transactionCode);
	// $this->utils->debug_log('OGP-18088,will processPreChecker().');
				try {
					$this->load->library(["lib_queue"]);
					$callerType = Queue_result::CALLER_TYPE_ADMIN;
					$caller = $playerId;
					$state  = null;
					$lang=null;
					$this->lib_queue->addRemoteProcessPreCheckerJob($walletAccountId, $callerType, $caller, $state, $lang);
					// $this->processPreChecker($walletAccountId);
				} catch (Exception $e) {
					$formatStr = 'Exception in processPreChecker(). (%s)';
					$this->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
				}
			}
		}else{
			$this->utils->debug_log('OGP-18088,skip processPreChecker().');
		}
	}

	private function verifyEnableWithdrawalPassword() {
		$verify_result = ['passed' => true, 'error_message' => ''];

		$withdrawVerificationMethod = $this->utils->getConfig('withdraw_verification');
		if($withdrawVerificationMethod != 'withdrawal_password'){
			$message = lang('Withdrawal password is disabled');
			$verify_result['error_code'] = self::CODE_WITHDRAWAL_PASSWORD_DISABLED;
			$verify_result['error_message'] = $message;
			$verify_result['passed'] = false;
		}
		return $verify_result;
	}

	private function processCryptoCurrencyWithdrawal($amount, $crypto, $banktype, $targetCurrency) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->utils->debug_log('=======================processCryptoCurrencyWithdrawal', $amount, $crypto, $banktype);

			// if(empty($crypto)){
			// 	$message = lang('Missing params: cryptoAmount');
			// 	$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
			// 	return $verify_result;
			// }
			$cryptoData = [];

			$cryptocurrency  = $this->utils->getCryptoCurrency($banktype);
			$defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];
			$reciprocalDecimalPlaceSetting = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
			$crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);

			$force_using_fixed_usd_stablecoin_rate = $this->utils->getConfig('force_using_fixed_usd_stablecoin_rate');
			$fixed_usd_stablecoin_rate = $this->utils->getConfig('fixed_usd_stablecoin_rate');

			$this->utils->debug_log(__METHOD__, 'processCryptoCurrencyWithdrawal cryptocurrency', $cryptocurrency, 'targetCurrency', $targetCurrency, 'force_using_fixed_usd_stablecoin_rate', $force_using_fixed_usd_stablecoin_rate, 'fixed_usd_stablecoin_rate', $fixed_usd_stablecoin_rate);

			if(
				($targetCurrency == 'USD') &&
				(in_array($cryptocurrency, ['USDT', 'USDC'])) &&
				($force_using_fixed_usd_stablecoin_rate)
			){
				$rate = $fixed_usd_stablecoin_rate;
				$convertCrypto = $fixed_usd_stablecoin_rate;
			}
			else{
				list($convertCrypto, $rate) = $this->utils->convertCryptoCurrency($amount, $defaultCurrency, $cryptocurrency,'withdrawal');
			}

			if(empty($rate)){
				$this->utils->debug_log("The crypto rate is not correct", $rate, $convertCrypto, $amount, $defaultCurrency, $cryptocurrency);
				$message = lang('The crypto rate is not correct');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			$verifyCrypto = number_format($amount * $crypto_to_currecny_exchange_rate/ $rate, $reciprocalDecimalPlaceSetting,'.','');
			$this->utils->debug_log('=======================processCryptoCurrencyWithdrawal convertCrypto', $convertCrypto, 'rate', $rate, 'crypto_to_currecny_exchange_rate', $crypto_to_currecny_exchange_rate, 'reciprocalDecimalPlaceSetting', $reciprocalDecimalPlaceSetting, 'verifyCrypto', $verifyCrypto);
			if($crypto != $verifyCrypto){
				$this->utils->debug_log("The conversion result is not correct",$rate,$crypto,$amount,$verifyCrypto);
				$message = lang('The conversion result is not correct');
				$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				return $verify_result;
			}

			$custom_withdrawal_rate = $this->config->item('custom_withdrawal_rate') ? $this->config->item('custom_withdrawal_rate') : 1;
			$player_rate = number_format($rate * $custom_withdrawal_rate, 4, '.', '');
			$cryptoData['rate'] = $rate;
			$cryptoData['player_rate'] = $player_rate;
			$cryptoData['cryptocurrency'] = $cryptocurrency;
			$verify_result['data'] = $cryptoData;

			$this->utils->debug_log('=======================processCryptoCurrencyWithdrawal verify_result', $verify_result);
			return $verify_result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
			return $verify_result;
		}
	}

	public function getWithdrawalFeeSettings($playerId, $WithdrawalFeeRule, $maxMonthlyWithdrawal){
		$firstDayOfThisMonth = date('Y-m-d H:i:s', strtotime('midnight first day of this month'));
		$todayDate = $this->utils->getNowForMysql();
		$upgRes = $this->group_level->queryLastGradeRecordRowBy($playerId, $firstDayOfThisMonth, $todayDate, null, 'request_time');

		if (!empty($upg_res)) {
			$monthlyWithdrawalTotal = $this->wallet_model->sumWithdrawAmount($playerId, $upgRes['request_time'], $todayDate, 0);
		}else{
			$monthlyWithdrawalTotal = $this->wallet_model->sumWithdrawAmount($playerId, $firstDayOfThisMonth, $todayDate, 0);
		}

		$freeFeeAmt = $maxMonthlyWithdrawal-$monthlyWithdrawalTotal;
		$freeMonthlyWithdrawalAmount = $freeFeeAmt > 0  ? $freeFeeAmt : 0;
		$player = $this->player_model->getPlayerArrayById($playerId);
		$withdrawalFeePercentage = $WithdrawalFeeRule[$player['levelId']];
		$this->utils->debug_log(__METHOD__ . "playerId:$playerId", "upgRes", $upgRes, "monthlyWithdrawalTotal", $monthlyWithdrawalTotal, "freeFeeAmt", $freeFeeAmt, "freeMonthlyWithdrawalAmount", $freeMonthlyWithdrawalAmount, "withdrawalFeePercentage", $withdrawalFeePercentage, "WithdrawalFeeRule", $WithdrawalFeeRule, "maxMonthlyWithdrawal", $maxMonthlyWithdrawal, "firstDayOfThisMonth", $firstDayOfThisMonth, "todayDate", $todayDate);

		return [$freeMonthlyWithdrawalAmount, $withdrawalFeePercentage];
	}

	public function getWithdrawalBankFeeSettings($WithdrawalBankFeeRule){
		$withdrawalBankFeeDetails = [];
		$type = 'percentage';

		foreach ($WithdrawalBankFeeRule as $code => $fee) {
			if ($code == 'USDT-ERC') {
				$type = 'amount';
			}

			$bankType = $this->playerapi_model->getBankTypeByBankCode(strtoupper($code));
			if (empty($bankType)) {
				continue;
			}
			$data = [
				'bankId' => $bankType['bankTypeId'], 'fee' => $fee, 'unit' => $type
			];
			$withdrawalBankFeeDetails[] = $data;
		}
		$this->utils->debug_log(__METHOD__, "withdrawalBankFeeDetails", $withdrawalBankFeeDetails, "WithdrawalBankFeeRule", $WithdrawalBankFeeRule);
		return $withdrawalBankFeeDetails;
	}
/*============================================= The following function havent't been tested =============================================*/

	//This function haven't been tested. Just extracted the cryptoCurrency related code inside of handleWithdrawalrequest to here
	private function ProcessSmsWithdrawalPromptActionRequest($player) {
		# send prompt message when withdrawal request is successful
		if ($this->utils->isEnabledFeature('enable_sms_withdrawal_prompt_action_request')) {

			$this->load->model(['cms_model', 'queue_result']);
			$this->load->library(["lib_queue", "sms/sms_sender"]);

			$dialingCode = $player['dialing_code'];
			$mobileNum = !empty($dialingCode)? $dialingCode.'|'.$player['contactNumber'] : $player['contactNumber'];
			$smsContent = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_WITHDRAWAL_REQUEST);
			$mobileNumIsVeridied = $player['verified_phone'];
			$isUseQueueToSend    = $this->utils->isEnabledFeature('enabled_send_sms_use_queue_server');
			$callerType = Queue_result::CALLER_TYPE_ADMIN;
			$caller = $playerId;
			$state  = null;
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

			if ($mobileNumIsVeridied && $isUseQueueToSend) {
				$this->lib_queue->addRemoteSMSJob($mobileNum, $smsContent, $callerType, $caller, $state, null);
			} else if ($mobileNumIsVeridied) {
				$this->sms_sender->send($mobileNum, $smsContent, $useSmsApi);
			}
		}
	}
}


