<?php

trait player_deposit_utils_module {

	private function verifyEnableUploadDepositFile() {
		// $this->utils->debug_log('============verifyEnableUploadDepositFile start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if(!$this->utils->isEnabledFeature('enable_deposit_upload_documents')){
				$message = lang('upload deposit documents is disabled by backend setup');
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

	private function verifyEmptyUploadFile($file) {
		// $this->utils->debug_log('============verifyEmptyUploadFile start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if (empty($file) || is_null($file['error'])) {
				$message = lang('Upload file not accessible');
				$verify_result['error_message'] = $message;
				$verify_result['passed'] = false;
			}
			else if (in_array(4, $file['error'])) {
				$message = lang('Upload file not accessible');
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

	private function verifyExistDeposiOrder($orderId) {
		// $this->utils->debug_log('============verifyExistDeposiOrder start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		// $orderId = 473792;
		try {
			$this->load->model(array('sale_order'));
			if (!$this->sale_order->existsSaleOrder($orderId)) {
				$message = lang('Latest generated order id does not exist.');
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

	private function verify3rdPartyApiId($saleOrder, $systemId) {
		// $this->utils->debug_log('============verify3rdPartyApiId start');
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			// $systemId = 6677777;
			if($saleOrder->system_id != $systemId) {
				$this->utils->debug_log('=========['.__METHOD__.'] parameters system_id, should be: ', $saleOrder->system_id, ', but the system_id of redirect url is: ', $systemId, ', secure_id: ', $saleOrder->secure_id);
				$message = lang('API id of latest generated order does not match.');
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

	private function verifyOrderAmount($saleOrder, $amount) {
		// $this->utils->debug_log('============verifyOrderAmount start');
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			// $amount = 6677777;
			if ( abs($saleOrder->amount - number_format($amount, 2, '.', '') ) > 1) {
				$this->utils->debug_log('=========['.__METHOD__.'] parameters amount, should be: ', $saleOrder->amount, ', but the amount of redirect url is: ', $amount, ', secure_id: ', $saleOrder->secure_id);
				$message = lang('Amount of latest generated order does not match.');
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

	private function verifyDoubleSubmit($saleOrder, $api) {
		// check sale order detail status, if greater than 100, then take it as duplicately submitting
		// $this->utils->debug_log('============verifyOrderAmount start');
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			if( ($saleOrder->detail_status > Sale_order::DEPOSIT_STATUS_CREATE_ORDER) && (!$api->allowSubmitSameOrderId()) ) {
				$message = lang('notify.duplicately_submiting_the_order_with_the_same_order_id');
				$this->utils->debug_log('=========['.__METHOD__.'] get exist saleOrder detail_status: ', $saleOrder->secure_id, $saleOrder->detail_status);
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

    private function _displayPaymentAccountData($payment_account, $has_detail = FALSE){
        $payment_account_data = [
            'payment_account_title' => sprintf('%s - %s', lang($payment_account->payment_type), $payment_account->payment_account_name),
            'payment_account_id' => $payment_account->payment_account_id,
            'bankTypeId' => $payment_account->bankTypeId,
            'flag' => $payment_account->flag,
            'min_deposit_trans' => $payment_account->vip_rule_min_deposit_trans,
            'max_deposit_trans' => $payment_account->vip_rule_max_deposit_trans,
            'account_icon_url' => $payment_account->account_icon_url,
            'second_category_flag' => $payment_account->second_category_flag,
            'bank_code' => $payment_account->bank_code,
        ];

        if($has_detail){
            $payment_account_data['account_icon_url'] = $payment_account->account_icon_url;
            $payment_account_data['account_image_url'] = $payment_account->account_image_url;
            $payment_account_data['payment_account_number'] = $payment_account->payment_account_number;
            $payment_account_data['payment_account_name'] = $payment_account->payment_account_name;
            $payment_account_data['payment_branch_name'] = $payment_account->payment_branch_name;
            $payment_account_data['min_deposit_trans'] = $payment_account->vip_rule_min_deposit_trans;
            $payment_account_data['max_deposit_trans'] = $payment_account->vip_rule_max_deposit_trans;
        }
     }

	private function verifyManualDepositCoolDownTime($playerId) {
		// $this->utils->debug_log('============verifyManualDepositCoolDownTime start');
		$verify_result = ['passed' => true, 'error_message' => ''];

        try {
		    $enable_manual_deposit_request_cool_down = json_decode($this->operatorglobalsettings->getSetting('manual_deposit_request_cool_down')->value,true);
		    $enable_manual_deposit_request_cool_down = !empty($enable_manual_deposit_request_cool_down) ? $enable_manual_deposit_request_cool_down : Sale_order::ENABLE_MANUALLY_DEPOSIT_COOL_DOWN;
		    $manual_deposit_request_cool_down_time   = json_decode($this->operatorglobalsettings->getSetting('manual_deposit_request_cool_down_time')->value,true);
		    $manual_deposit_request_cool_down_time   = !empty($manual_deposit_request_cool_down_time) ? $manual_deposit_request_cool_down_time : Sale_order::DEFAULT_MANUALLY_DEPOSIT_COOL_DOWN_MINUTES;

		    if($enable_manual_deposit_request_cool_down == Sale_order::ENABLE_MANUALLY_DEPOSIT_COOL_DOWN){
		        $manually_deposit_cool_down_minutes = $this->getDepositRequesCoolDownTime($manual_deposit_request_cool_down_time);
		        //check cold down time
		        $lastOrder=$this->sale_order->getLastUnfinishedManuallyDeposit($playerId);
		        if ($lastOrder && !$this->utils->isTimeoutNow($lastOrder['created_at'], $manually_deposit_cool_down_minutes)) {
		            $getTimeLeft = $this->utils->getMinuteBetweenTwoTime($lastOrder['created_at'],$manually_deposit_cool_down_minutes);
		            //not reach cool down time
		            $message = sprintf(lang('hint.manually.deposit.cool.down'), $manually_deposit_cool_down_minutes,$getTimeLeft);
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

	private function verifyResponsibleGamingDepositLimits($playerId, $depositAmount) {
		// $this->utils->debug_log('============verifyResponsibleGamingDepositLimits start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$this->load->model(array('sale_order', 'banktype', 'sale_orders_notes', 'sale_orders_timelog'));
			if ($this->utils->isEnabledFeature('responsible_gaming')) {
				$this->load->library(array('player_responsible_gaming_library'));
				//deposit limit hint
				if ($this->player_responsible_gaming_library->inDepositLimits($playerId, $depositAmount)) {
					$message = lang('Deposit Limits Effect, cannot make deposit');
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

	private function verifyExistsUnfinishedManualDeposit($playerId) {
		// $this->utils->debug_log('============verifyExistsUnfinishedManualDeposit start');
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			if($this->utils->isEnabledFeature('only_allow_one_pending_deposit')){
				$exists=$this->sale_order->existsUnfinishedManuallyDeposit($playerId);
				if($exists){
					$message = lang('Sorry, your last deposit request is not done, so you can not start new request');
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

	private function verifyPaymentAccountForbidden($payment_account, $playerId) {
		$verify_result = ['passed' => true, 'error_message' => ''];

		try {
			if (empty($payment_account)) {
				$message = sprintf(lang('gen.error.forbidden'), lang('pay.depmethod'));
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

	private function verifyDepositAmountLessThanMin($payment_account, $depositAmount) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = lang('notify.43');
			$minDeposit = $payment_account->vip_rule_min_deposit_trans;
			if (($depositAmount <= 0) || ($depositAmount < $minDeposit)) {
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

	private function verifyDepositAmountGreaterThanMax($payment_account, $depositAmount) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$maxDepositDaily = 0;
			$message = lang('notify.46');
			$maxDeposit = $payment_account->vip_rule_max_deposit_trans;
			$this->utils->debug_log('============'. __METHOD__ , 'depositAmount ='.$depositAmount, 'maxDeposit ='.$maxDeposit);
			if ($maxDeposit > 0) {
				if($depositAmount > $maxDeposit) {
					$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
				}
				elseif ($maxDepositDaily > 0) {
					$playerTotalDailyDeposit = $this->transactions->sumDepositAmountToday($playerId);
					if (($playerTotalDailyDeposit + $depositAmount) >= $maxDepositDaily) {
						$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
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

	private function verifyPaymentAccountAvailable($payment_account_id) {
		$verify_result = ['passed' => true, 'error_message' => ''];
		try {
			$message = lang('payment account is inactive');
			if (!$this->payment_account->checkPaymentAccountActive($payment_account_id)) {
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

	public function depositApiOutput($data, $systemId = null) {
		$list = [];
		if(empty($data)) {
			return $data;
		}

		foreach ($data as $key => $value) {
			switch ($key) {
				case 'pay.sale_order_id':
					$list['orderId'] = $value;
					break;
				case '3rdParty USDT Deposit':
					$list['thirdPartyCryptoAmount'] = $value;
					break;
				case 'financial_account.bankaccount.walletaddress':
					$list['walletAddress'] = $value;
					break;
				case 'collection.label.6':
					$list['requestedOn'] = $this->playerapi_lib->formatDateTime($value);
					break;
				case 'collection.label.7':
					$list['expiredOn'] = $this->playerapi_lib->formatDateTime($value);
					break;
				default:
					break;
			}
		}

		if(empty($list)) {
			return $data;
		}

		$this->utils->debug_log('============'. __METHOD__ .' systemId: ', $systemId, 'list: ', $list);
		return $list;
	}
/*============================================= The following function havent't been tested =============================================*/

    //This function haven't been tested. Just extracted the cryptoCurrency related code inside of handleManualPayment to here
	private function verifyUploadDocuments($file1, $file2) {
		try {
			if($this->utils->isEnabledFeature('enable_deposit_upload_documents')){
				$response_1 = $this->upload_attached_document($file1,$saleOrder['id'],$playerId,true);
				$response_2 = $this->upload_attached_document($file2,$saleOrder['id'],$playerId,true);
				if(isset($response_1['status'])) {
					if($response_1['status'] != 'success') {
						$message = lang('File')."1: ".lang('Upload failed.');
						$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
					}
				}
				if(isset($response_2['status'])) {
					if($response_2['status'] != 'success') {
						$message = lang('File')."2: ".lang('Upload failed.');
						$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $message);
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

	//This function haven't been tested. Just extracted the cryptoCurrency related code inside of handleManualPayment to here
	private function processCryptoCurrency($payment_account) {
		#cryptocurrency deposit
		$banktype = $this->banktype->getBankTypeById($payment_account->bankTypeId);
		if($this->utils->isCryptoCurrency($banktype)){
			$defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];
			$crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
			if(!empty($request_cryptocurrency_rate) && !empty($cryptoQty)){
				if($depositAmount != number_format(($request_cryptocurrency_rate * $cryptoQty)/$crypto_to_currecny_exchange_rate,2,'.','')){
					$message = lang('The conversion result is not correct');
					return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				}
			}
			$cryptocurrency = $this->utils->getCryptoCurrency($banktype);

			list($crypto, $rate) = $this->utils->convertCryptoCurrency($depositAmount, $cryptocurrency, $cryptocurrency, 'deposit');

			$custom_deposit_rate = $this->config->item('custom_deposit_rate') ? $this->config->item('custom_deposit_rate') : 1;

			$this->load->model(array('playerbankdetails'));
			$bankDetails = $this->playerbankdetails->getBankDetailsById($playerBankDetailsId);

			if($this->utils->getConfig('disabel_deposit_bank')){
			    $payment_type = 'withdrawal';
			}else{
			    $payment_type = 'deposit';
			}

 			if($payment_type == 'withdrawal'){
                if(is_array($cryptoPaymentAccount) && array_key_exists($cryptocurrency, $cryptoPaymentAccount)){
                    $cryptoAddress = $cryptoPaymentAccount[$cryptocurrency]['bankAccountNumber'];
                }else{
                    $cryptoAddress = 'null';
                }
            }else if($payment_type == 'deposit'){
                $cryptoAddress = $depositAccountNo;
            }else{
                $cryptoAddress = 'null';
            }

			$crypto      = $cryptoQty;
			$player_rate = $request_cryptocurrency_rate;
			$rate = $request_cryptocurrency_rate;
			$crypto_notes = 'Wallet Address: '. $cryptoAddress;
			$crypto_notes .= ' | '.$cryptocurrency.': '.$crypto.' | Crypto Real Rate: '.$rate;
			$this->utils->debug_log('=======================cryptocurrency deposit_notes', $deposit_notes);
		}

		if (isset($crypto)) {
			$message['msg'] = sprintf(lang('Please transfer to'), $payment_account->payment_account_number, $crypto, $this->utils->formatCurrencyNoSymwithDecimal($player_rate, 8));
			//$this->utils->formatCurrencyNoSymwithDecimal($player_rate, 8)
			$this->sale_orders_notes->add($crypto_notes, Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $saleOrder['id']);
			if(isset($saleOrder['id'])){
				$deposit_usdt_order_args = [
					$saleOrder['id'],
					$crypto,
					$rate,
					$this->utils->getNowForMysql(),
					$this->utils->getNowForMysql(),
					$cryptocurrency,
				];
				$cryptoSaleOrderId = call_user_func_array([$this->sale_order, 'createCryptoDepositOrder'], $deposit_usdt_order_args);
			}
		}
	}

	//This function haven't been tested. Just extracted the cryptoCurrency related code inside of handleManualPayment to here
	private function process3rdPartyApiWhenManualDeposit() {
		$third_api_id = $this->utils->getConfig('third_party_api_id_when_manual_deposit');
		if(!empty($third_api_id)){
			$api = $this->utils->loadExternalSystemLibObject($third_api_id);
			if (!empty($api)) {
				$api->manualPaymentUrlForm($saleOrder, $playerId, $depositAmount, $deposit_time, $payment_account_id, $playerBankDetailsId);
			}else{
				return $this->utils->debug_log('--- not exist systemId or manualPaymentUrlForm function ---');
			}
		}
	}

	private function processPromoRules($playerId, $promo_cms_id, $transferAmount, &$error=null, $subWalletId=null){
		if(!empty($promo_cms_id)){

			list($promorule, $promoCmsSettingId)=$this->promorules->getByCmsPromoCodeOrId($promo_cms_id);

			//simple check
			//check sub wallet only
			if($this->promorules->isTransferPromo($promorule)){

				//check
				if ($promorule['depositConditionNonFixedDepositAmount'] == Promorules::NON_FIXED_DEPOSIT_MIN_MAX) {
					if ($transferAmount >= $promorule['nonfixedDepositMinAmount'] && $transferAmount <= $promorule['nonfixedDepositMaxAmount']) {
					} else {
						$error = lang('notify.37');
						return [null, null, null];
					}
				}

				$trigger_wallets=$promorule['trigger_wallets'];
				$trigger_wallets_arr=[];
				if(!empty($trigger_wallets)){
					$trigger_wallets_arr=explode(',',$trigger_wallets);
				}
				if(!in_array($subWalletId, $trigger_wallets_arr)){
					$this->utils->error_log('subWalletId should be ', $trigger_wallets_arr ,'current',$subWalletId);
					// $message = 'Only trigger on transfer right sub-wallet';
					$error = lang('Must choose correct sub-wallet');
					return [null, null, null];
				}

			}elseif($this->promorules->isDepositPromo($promorule)){
				//check
				if ($promorule['depositConditionNonFixedDepositAmount'] == Promorules::NON_FIXED_DEPOSIT_MIN_MAX) {
					if ($transferAmount >= $promorule['nonfixedDepositMinAmount'] && $transferAmount <= $promorule['nonfixedDepositMaxAmount']) {
					} else {
						$error = lang('notify.37');
						return [null, null, null];
					}
				}

			}

			return [$promoCmsSettingId, $promorule['promorulesId'], $promorule];
		}

		return [null, null, null];
	}
}