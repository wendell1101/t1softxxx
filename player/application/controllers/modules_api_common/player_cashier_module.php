<?php

/**
 * uri: see routes.php /payment-methods, /payment-requests, /withdraw-conditions, /withdraw-requests
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 */
trait player_cashier_module {

	public function cashier($action, $additional=null, $append=null){
		if(!$this->initApi()){
			return;
		}

		$this->load->library(['playerapi_lib', 'payment_library']);
		$this->load->model(['sale_order', 'comapi_reports', 'playerapi_model', 'payment_account', 'playerbankdetails', 'external_system', 'sale_orders_status_history']);
		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'payment-methods':
				return $this->getPaymentMethodsByPlayerId($this->player_id);
				break;
			case 'payment-requests':
				if($request_method == 'GET') {
					if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0)){
						return $this->getDepositRequestByOrderId($additional);
					}
					else if($additional == 'pending-amount') {
						return $this->getUnapprovedDepositAmountByPlayerId($this->player_id);
					}
					else {
						return $this->getPaymentRequestsByPlayerId($this->player_id);
					}
				}
				else if($request_method == 'POST') {
					return $this->postDepositRequest();
				}
				break;

			case 'withdraw-conditions':
				if($request_method == 'GET') {
					return $this->getWithdrawalConditionsByPlayerId($this->player_id);
				}
				break;

			case 'withdraw-conditions-completed':
				if($request_method == 'GET') {
					return $this->getWithdrawalConditionsCompletedByPlayerId($this->player_id);
				}
				break;

			case 'withdraw-requests':
				if($request_method == 'GET') {
					if($additional == 'pending-amount') {
						return $this->getUnapprovedWithdrawalAmountByPlayerId($this->player_id);
					}
					else {
						return $this->getWithdrawalRequestsByPlayerId($this->player_id);
					}
				}
				else if($request_method == 'POST') {
					return $this->postWithdrawalRequest();
				}
				break;

			case 'payment-settings':
				if($request_method == 'GET') {
					return $this->getPaymentSettingsByPlayerId($this->player_id);
				}
				break;

			case 'file-upload':
				if($request_method == 'POST') {
					return $this->postDepositUpload();
				}
				break;

			case 'fetch-deposit-file':
				return $this->fetchDepositFile($additional, $append);
		}
		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function getPaymentMethodsByPlayerId($player_id) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$available_deposit_collection_accounts = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $currency) {
			return $this->getDepositAccountsByPlayerId($player_id, $currency);
		});

		$result['code'] = empty($available_deposit_collection_accounts) ? self::CODE_OK : $result['code'];
		$result['data'] = $available_deposit_collection_accounts;
		return $this->returnSuccessWithResult($result);
	}

	private function getDepositAccountsByPlayerId($player_id, $target_currency = null) {
		$bank_details = $this->playerbankdetails->getBankDetails($player_id);
		$disable_bank_code_where_interbank_transfer = $this->utils->getConfig('disable_bank_code_where_interbank_transfer');
        $external_system_currency_setting = $this->utils->getConfig('external_system_currency_setting');

		$payment_all_accounts = $this->payment_account->getAvailableDefaultCollectionAccount($player_id);
		$payment_manual_accounts = ($payment_all_accounts[MANUAL_ONLINE_PAYMENT]['enabled']) ? $payment_all_accounts[MANUAL_ONLINE_PAYMENT]['list'] : [];

		if($payment_all_accounts[LOCAL_BANK_OFFLINE]['enabled']){
			foreach($payment_all_accounts[LOCAL_BANK_OFFLINE]['list'] as $payment_account){
				$payment_manual_accounts[] = $payment_account;
			}
		}
		uasort($payment_manual_accounts, function($a, $b){
		    return ($a->payment_order < $b->payment_order) ? -1 : 1;
		});

		$existParticularBank = false;
		if (!empty($disable_bank_code_where_interbank_transfer)){
			foreach ($bank_details['deposit'] as $player_bank){
				if($player_bank['bank_code'] == $disable_bank_code_where_interbank_transfer){
					$existParticularBank = true;
				}
			}
			if(!$existParticularBank){
			    foreach ($payment_manual_accounts as $key => $payment_manual) {
					if( $payment_manual->bankCode == $disable_bank_code_where_interbank_transfer){
						unset($payment_manual_accounts[$key]);
					}
			    }
			}
		}
		$payment_auto_accounts = ($payment_all_accounts[AUTO_ONLINE_PAYMENT]['enabled']) ? $payment_all_accounts[AUTO_ONLINE_PAYMENT]['list'] : [];

		$deposit_all_accounts = array_merge($payment_auto_accounts, $payment_manual_accounts);
        $result_output = [];
        $randomKey = uniqid();
		foreach ($deposit_all_accounts as $key => $collection_account_item) {
            $external_system_id = (int)$collection_account_item->external_system_id;
			$result_output[$key]['bankAccountInfo']['accountHolderName'] = $collection_account_item->payment_account_name;
			$result_output[$key]['bankAccountInfo']['accountNumber'] = $collection_account_item->payment_account_number;
			$result_output[$key]['bankAccountInfo']['bankBranch'] = $collection_account_item->payment_branch_name;
			$result_output[$key]['bankAccountInfo']['bankId'] = (int)$collection_account_item->bankTypeId;
			$result_output[$key]['bankAccountInfo']['bankName'] = lang($collection_account_item->payment_type);
			$result_output[$key]['id'] = (int)$collection_account_item->id;
			// $result_output[$key]['logoUrl'] = !empty($collection_account_item->account_icon_filepath) ? $collection_account_item->account_icon_filepath : '';
			$result_output[$key]['logoUrl'] = !empty($collection_account_item->account_icon_url) ? $collection_account_item->account_icon_url."&random={$randomKey}": '';
			$result_output[$key]['maxDeposit'] = (double)$collection_account_item->vip_rule_max_deposit_trans;
			$result_output[$key]['minDeposit'] = (double)$collection_account_item->vip_rule_min_deposit_trans;
			$result_output[$key]['name'] = $collection_account_item->payment_account_name;
			$result_output[$key]['note'] = $collection_account_item->notes;
			$result_output[$key]['paymentApi']['id'] = $external_system_id;
			$result_output[$key]['paymentApi']['name'] = !empty($external_system_id) ? $this->external_system->getSystemName($external_system_id) : '';
			$result_output[$key]['presetAmounts'] = !empty($collection_account_item->preset_amount_buttons) ? array_map('intval', explode('|', $collection_account_item->preset_amount_buttons)) : [];
			$result_output[$key]['type'] = (int)$this->playerapi_lib->matchOutputDepositSecondCategoryFlagStatus($collection_account_item->second_category_flag);
			$result_output[$key]['methodType'] = (int)$this->playerapi_lib->matchOutputDepositFlagStatus($collection_account_item->flag);
            if(isset($external_system_currency_setting[$external_system_id])){
                $source_currency = $external_system_currency_setting[$external_system_id];
                $result_output[$key]['sourceCurrency'] = strtoupper($source_currency);
                $crypto_rate = $this->calculateCryptoRate(strtoupper($target_currency), strtoupper($source_currency), 0);
                if(!empty($crypto_rate['result']['rate'])){
                    $result_output[$key]['exchangeRates'] = $crypto_rate['result']['rate'];
                }
            }
		}
		return $result_output;
	}

	protected function getPaymentRequestsByPlayerId($player_id) {
		try {
			$validate_fields = [
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'requestedDateEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
				['name' => 'requestedDateStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
				['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'status', 'type' => 'array[int]', 'required' => false, 'length' => 0],
			];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

			if(!$is_validate_basic_passed['validate_flag']){
				$this->comapi_log(__METHOD__,'getPaymentRequestsByPlayerId check validation state',
										['requestBody' => $request_body,'isValidateBasicPassed' => $is_validate_basic_passed]);
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			$createdAtStart = !empty($request_body['requestedDateStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['requestedDateStart']):$this->playerapi_lib->convertDateTimeToMysql( date('Y-m-d').' 00:00:00');
			$createdAtEnd = !empty($request_body['requestedDateEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['requestedDateEnd']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 23:59:59');

			$time_start     = $createdAtStart;
			$time_end       = $createdAtEnd;
			$limit		    = !empty($request_body['limit']) ? $request_body['limit'] : 20;
			$sort 		    = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
			$page 	  	    = !empty($request_body['page']) ? $request_body['page'] : 1;
			$currency 		= !empty($request_body['currency']) ? $request_body['currency'] : null;
			$input_status   = !empty($request_body['status']) ? $request_body['status'] : [];
			$deposit_status = [];

			if(!empty($input_status)){
				foreach($input_status as $val){
					$match_result = $this->playerapi_lib->matchInputDepositStatus($val);
					if(!empty($match_result)){
						$deposit_status[] = $match_result;
					}
				}
			}

			$all_deposit_orders = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $time_start, $time_end, $limit, $sort, $deposit_status, $page) {
				return $this->playerapi_model->getPaymentRequestsByPlayerId($player_id, $time_start, $time_end, $limit, $sort, $deposit_status, $page);
			});

			$rebuild_key_arr = ['paymentMethod', 'player', 'deposit_order_comments', 'deposit_order_status', 'approvalDate', 'expirationDate', 'requestedDate', 'updatedAt', 'deposit_order_enable_upload_file', 'deposit_upload_file_list'];
			$all_deposit_orders['list'] = $this->playerapi_lib->customizeApiOutput($all_deposit_orders['list'], $rebuild_key_arr);
			$all_deposit_orders['list'] = $this->playerapi_lib->convertOutputFormat($all_deposit_orders['list'], ['fromStatus', 'toStatus']);

			$result['code'] = Playerapi::CODE_OK;
			$result['data'] = $all_deposit_orders;

			return $this->returnSuccessWithResult($result);

		} catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);
            return $this->returnErrorWithResult($result);
        }
	}

	protected function getDepositRequestByOrderId($order_id) {
		$result=['code'=> Playerapi::CODE_OK];
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$deposit_order = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($order_id) {
			return $this->playerapi_model->getDepositRequestByOrderId($order_id);
		});
		$rebuild_key_arr = ['paymentMethod', 'player', 'deposit_order_comments', 'deposit_order_status', 'approvalDate', 'expirationDate', 'requestedDate', 'updatedAt', 'deposit_order_enable_upload_file', 'deposit_upload_file_list'];
		$deposit_order = !empty($deposit_order) ? $this->playerapi_lib->customizeApiOutput([$deposit_order], $rebuild_key_arr) : [];
		$deposit_order = $this->playerapi_lib->convertOutputFormat($deposit_order, ['fromStatus', 'toStatus']);
		$output_deposit_order = !empty($deposit_order) ? $deposit_order[0] : null;
		$result['data'] = $output_deposit_order;
		$result['code'] = empty($deposit_order) ? Playerapi::CODE_DEPOSIT_REQUEST_NOT_FOUND : $result['code'];
		return $this->returnSuccessWithResult($result);
	}

	protected function getUnapprovedDepositAmountByPlayerId($player_id) {
		$result=['code'=> Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$data = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id) {
			return $this->playerapi_model->getUnapprovedDepositAmountByPlayerId($player_id);
		});
		$output = $this->playerapi_lib->convertOutputFormat([$data]);
		$result['data'] = (int)$output[0];
		return $this->returnSuccessWithResult($result);
	}

	protected function getWithdrawalConditionsByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'createdAtEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'createdAtStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'status', 'type' => 'int', 'required' => false, 'length' => 0],
		];

		$result=['code'=>Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$createdAtStart = !empty($request_body['createdAtStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtStart']):null;
		$createdAtEnd = !empty($request_body['createdAtEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtEnd']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d H:i:s'));

		$this->utils->debug_log('OGP-33235', 'createdAtStart:', $createdAtStart, 'createdAtEnd:', $createdAtEnd);

		$time_start = $createdAtStart;
		$time_end = $createdAtEnd;
		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
		$status = !empty($request_body['status']) ? $request_body['status'] : null;
		$status = $this->playerapi_lib->matchInputWithdrawalConditionIsFinishStatus($status);
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;

		$all_withdrawal_condition_list = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $time_start, $time_end, $limit, $sort, $page, $currency, $status) {

			$get_details = $this->getPlayerWithdrawConditionDetail($player_id);

			$wc_details = [
				'currency' => strtoupper($currency ?: $this->currency),
				'unfinishedBet' => $get_details['unfinishedBet'],
				'unfinishedDeposit' => $get_details['unfinishedDeposit']
			];

			$wc_list = $this->playerapi_model->getWithdrawalConditionsByPlayerId($player_id, $time_start, $time_end, $limit, $sort, $page, $status);

			$wc_data = array_map(function($wc) {
				
				if(!empty($wc['singleQuestTitle'])){
					$promoName = $wc['singleQuestTitle'];
				}else if(!empty($wc['multiQuestTitle'])){
					$promoName = $wc['multiQuestTitle'];
				}else{
					$promoName = $wc['promoName'];
				}

				return [
					'id' => $wc['id'],
					'sourceType' => lang('withdraw_conditions.source_type.' . $wc['sourceType']) ?:  lang('lang.norecyet'),
					'promoName' => $promoName,
					'depositAmount' => $wc['depositAmount'],
					'bonusAmount' => $wc['bonusAmount'],
					'createdAt' => $this->playerapi_lib->formatDateTime($wc['startedAt']),
					'withdrawConditionAmount' => $wc['withdrawConditionAmount'],
					'betAmount' => $wc['betAmount'],
					'status' => $this->playerapi_lib->matchOutputWithdrawalConditionIsFinishStatus($wc['status']),
				];
			}, $wc_list['list']);

			$wc_list['list'] = $this->playerapi_lib->convertOutputFormat($wc_data);

			return array_merge($wc_details, $wc_list);
		});

		$all_withdrawal_condition_list = $this->playerapi_lib->convertOutputFormat($all_withdrawal_condition_list);
		$result['data'] = $all_withdrawal_condition_list;
		return $this->returnSuccessWithResult($result);
	}

	public function getWithdrawalConditionsCompletedByPlayerId($player_id) {
		$this->load->model(['withdraw_condition']);

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$wc_unfinished = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id) {
            $hasUnfinishedRecords = $this->withdraw_condition->onlyCheckHasUnfinishedWithdrawalCondictionRecords($player_id);
            if(!$hasUnfinishedRecords){
                return false;
            }
			return $this->withdraw_condition->existUnfinishWithdrawConditions($player_id);
		});

		$result['data']['allCompleted'] = ($wc_unfinished === false);
		return $this->returnSuccessWithResult($result);
	}

	public function getPlayerWithdrawConditionDetail($playerId){
		$this->load->model(['withdraw_condition']);
		$data = [];
        $res = $this->withdraw_condition->computePlayerWithdrawalConditionsWithDepositCondition($playerId);

        if(!empty($res)){
            $data = [
                'totalRequiredBet'	=> $res['totalRequiredBet'] ,
                'currentTotalBet'	=> $res['totalPlayerBet'] ,
                'unfinishedBet'		=> $res['unfinished_bet'],
                'unfinishedDeposit' => $res['unfinished_deposit']
            ];
        }

		return $data;
    }

	protected function getWithdrawalRequestsByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'requestedDateEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'requestedDateStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'status', 'type' => 'int', 'required' => false, 'length' => 0],
		];

		$result=['code'=>Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$createdAtStart = !empty($request_body['requestedDateStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['requestedDateStart']):null;
		$createdAtEnd = !empty($request_body['requestedDateEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['requestedDateEnd']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d H:i:s'));

		$time_start = $createdAtStart;
		$time_end = $createdAtEnd;
		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
		$status = isset($request_body['status']) ? $request_body['status'] : '9999';
		$status = $this->playerapi_lib->matchInputWithdrawalStatus($status);
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;

		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$all_withdrawal_orders = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $time_start, $time_end, $limit, $sort, $status, $page) {
			return $this->playerapi_model->getWithdrawalRequestsByPlayerId($player_id, $time_start, $time_end, $limit, $sort, $status, $page);
		});

		$rebuild_key_arr = ['withdrawal_approve_date', 'bank_account', 'player', 'withdiawal_order_comments', 'withdrawal_order_status', 'requestedDate', 'updatedAt'];
		$all_withdrawal_orders['list'] = $this->playerapi_lib->customizeApiOutput($all_withdrawal_orders['list'], $rebuild_key_arr);
		$all_withdrawal_orders['list'] = $this->playerapi_lib->convertOutputFormat($all_withdrawal_orders['list'], ['fromStatus', 'toStatus']);

		foreach ($all_withdrawal_orders['list'] as &$item) {
			$item['currency'] = strtoupper($currency ?: $this->currency);
		}

		$result['data'] = $all_withdrawal_orders;

		return $this->returnSuccessWithResult($result);
	}

	protected function getUnapprovedWithdrawalAmountByPlayerId($player_id) {
		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$data = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id) {
			return $this->playerapi_model->getUnapprovedWithdrawalAmountByPlayerId($player_id);
		});
		$output = $this->playerapi_lib->convertOutputFormat([$data]);
		$result['data'] = (int)$output[0];
		return $this->returnSuccessWithResult($result);
	}

	protected function postDepositUpload() {
		if (!$this->initApi()) {
			return;
		}
		$validate_fields = [
			['name' => 'depositId', 'type' => 'string', 'required' => true, 'length' => 0],
			['name' => 'file', 'type' => 'file[]', 'required' => true],
		];
		$result = ['code' => Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = Playerapi::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$deposit_id = $request_body['depositId'];
		$image = $_FILES['file'];
		$this->comapi_log("=====================".__METHOD__, 'files', $_FILES);
		$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;
		$result = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($deposit_id, $image) {
			return $this->handleDepositFileUpload($deposit_id, $image);
		});

		return $this->returnSuccessWithResult($result);
	}

	protected function postDepositRequest()
	{
		try {
			$validate_fields = [
				['name' => 'amount', 'type' => 'positive_double', 'required' => true, 'length' => 0],
				['name' => 'bankId', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'paymentMethodId', 'type' => 'int', 'required' => true, 'length' => 0],
				['name' => 'forceAssignReturnActionType', 'type' => 'string', 'required' => false, 'length' => 0, 'allowed_content' => ['DISPLAY', 'REDIRECT_PAGE', 'SUBMIT_POST_FORM', 'SCAN_QR', 'CRYPTO', 'HTML', 'ERROR']], // forceAssignReturnActionType is an hidden param for frontend integration to see different types of return params. It's not in API Doc
				['name' => 'campaignId', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'cryptoAmount', 'type' => 'positive_double', 'required' => false, 'length' => 0],
			];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, 'postDepositRequest validation state', ['requestBody' => $request_body, 'isValidateBasicPassed' => $is_validate_basic_passed]);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			# forceAssignReturnActionType is an hidden param for frontend integration to see different types of return params
			$force_assign_reutrn_redirect_type = isset($request_body['forceAssignReturnActionType']) ? $request_body['forceAssignReturnActionType'] : null;

			$amount 	     		= $request_body['amount'];
			$player_id 		 	    = $this->player_id;
			$player_bank_details_id = isset($request_body['bankId']) ? $request_body['bankId'] : '0';
			$payment_account_id 	= $request_body['paymentMethodId'];
			$payment_account 	    = $this->payment_account->getPaymentAccount($payment_account_id);
			$payment_api_id  	    = !empty($payment_account) ? $payment_account->external_system_id : null;
			$promo_cms_id 	 		= !empty($request_body['campaignId']) ? $request_body['campaignId'] : null;
			$currency 		 		= !empty($request_body['currency']) ? $request_body['currency'] : null;
			$crypto_amount 			= !empty($request_body['cryptoAmount']) ? $request_body['cryptoAmount'] : 0;

			if(!empty($payment_api_id)) {
				$output_with_result = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($payment_account_id, $payment_api_id, $amount, $player_id, $player_bank_details_id, $force_assign_reutrn_redirect_type, $promo_cms_id, $crypto_amount, $currency) {
					return $this->handle3rdPartyPayment($payment_account_id, $payment_api_id, $amount, $player_id, null, 'true', $player_bank_details_id, $force_assign_reutrn_redirect_type, $promo_cms_id, $crypto_amount, $currency);
				});
				return $output_with_result;
			}
			else {
				$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($amount, $player_id, $player_bank_details_id, $payment_account_id, $promo_cms_id) {
					return $this->handleManualPayment($amount, $player_id, $player_bank_details_id, $payment_account_id, $promo_cms_id);
				});

				if($output['data']['manual']['success']){
					return $this->returnSuccessWithResult($output);
				}else{
					throw new APIException($output['data']['manual']['message'], $output['code']);
				}
			}
		}
		catch (\APIException $ex) {
		    $result['code'] = $ex->getCode();
			$result['error_description']= $ex->getMessage();
		    $this->comapi_log(__METHOD__, 'APIException', $result);

		    return $this->returnErrorWithResult($result);
		}
	}

	protected function postWithdrawalRequest() {
		try {
			$validate_fields = [
				['name' => 'accountHolderName', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'accountNumber', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'amount', 'type' => 'positive_double', 'required' => true, 'length' => 0],
				['name' => 'bankBranch', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'bankId', 'type' => 'int', 'required' => true, 'length' => 0],
				['name' => 'withdrawPassword', 'type' => 'string', 'required' => false, 'length' => 0],
				['name' => 'cryptoAmount', 'type' => 'positive_double', 'required' => false, 'length' => 0],
			];

			$result=['code'=>self::CODE_OK];
			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			$pixSystemInfo = $this->utils->getConfig('pix_system_info');
			if($pixSystemInfo['identify_cpf_numer_on_kyc']['enabled']){
				if(!$this->verifyCpfStatusOnKyc($this->player_id)){
					throw new APIException(lang('CPF has not yet passed KYC verification'), self::CODE_KYC_NOT_VERIFIED);
				}
			}

			if ($this->utils->getConfig('enable_sms_verified_phone_in_withdrawal')) {
                $player_verified_phone = $this->player_model->isVerifiedPhone($this->player_id);
                if (!$player_verified_phone) {
					throw new APIException(lang('withdrawal.msg8'), self::CODE_PHONENUMBER_NOT_VERIFIED);
                }
            }

			$bank_details_id = $request_body['bankId'];
			// $bank_type_id =  $request_body['bankId'];
			$bank_account_number = $request_body['accountNumber'];
			$bank_account_full_name = !empty($request_body['accountHolderName']) ? $request_body['accountHolderName'] : '';
			$amount = $request_body['amount'];
			$bank_branch = !empty($request_body['bankBranch']) ? $request_body['bankBranch'] : '';
			$withdrawal_password = !empty($request_body['withdrawPassword']) ? $request_body['withdrawPassword'] : null;
			$crypto_amount = !empty($request_body['cryptoAmount']) ? $request_body['cryptoAmount'] : 0;
			$currency = !empty($request_body['currency']) ? $request_body['currency'] : null;

            $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use (
                $bank_details_id, 
                $bank_account_number, 
                $bank_account_full_name, 
                $amount, 
                $bank_branch, 
                $withdrawal_password, 
                $crypto_amount, 
                $currency
                ) {
                return $this->handleWithdrawalrequest($bank_details_id, $bank_account_number, $bank_account_full_name, $amount, $bank_branch, $withdrawal_password, $crypto_amount, $currency);
            });
			return $this->returnSuccessWithResult($output);
		}
		catch (\APIException $ex) {
		    $result['code'] = $ex->getCode();
			$result['error_description']= $ex->getMessage();
		    $this->comapi_log(__METHOD__, 'APIException', $result);

		    return $this->returnErrorWithResult($result);
		}
	}

	protected function fetchDepositFile($sale_order_id, $player_attached_proof_file_id) {
		if (!$this->initApi()) {
			return;
		}
        $player_internal_url = $this->utils->getPlayerInternalUrl('player',PLAYER_INTERNAL_DEPOSIT_RECEIPT_PATH);
        $receipt_path = $player_internal_url . $sale_order_id . '/' . $player_attached_proof_file_id.'/enabled';
        header('Location: '.$receipt_path);
	}

	/**
	 * overview : payment
	 *
	 * @param int		$systemId
	 * @param double	$amount
	 * @param int		$playerId
	 * @param int		$player_promo_id
	 * @param string	$enabledSecondUrl
	 * @param int		$bankId
	 * @param int		$orderId
	 */
	private function handle3rdPartyPayment($payment_account_id, $systemId, $amount, $playerId = null, $player_promo_id = null, $enabledSecondUrl = 'true', $bankId = null, $force_assign_reutrn_redirect_type=null, $promo_cms_id=null, $crypto_amount=0, $currency=null) {
        $this->load->model(array('sale_order', 'sale_orders_status_history'));
		$this->load->vars('content_template', 'default_iframe.php'); # this page uses default content template in stable_center2
		$api = $this->utils->loadExternalSystemLibObject($systemId);
		$payment_account = $this->payment_account->getPaymentAccountWithVIPRule($payment_account_id, $playerId);

		// Check before create sale order
		$result = [
			'code' => self::CODE_DEPOSIT_REQUEST_OPERATION_FAILED,
			'data' => [
				'actionType' => 'ERROR',
			],
		];

		$verify_function_list = [
			[ 'name' => 'verifyPaymentAccountForbidden', 'params' => [$payment_account, $playerId] ],
			[ 'name' => 'verifyDepositAmountLessThanMin', 'params' => [$payment_account, $amount] ],
			[ 'name' => 'verifyDepositAmountGreaterThanMax', 'params' => [$payment_account, $amount] ],
			[ 'name' => 'verifyAvailableBankTypeOnDeposit', 'params' => [$payment_account->payment_type_id] ],
			[ 'name' => 'verifyPaymentAccountAvailable', 'params' => [$payment_account_id] ]
		];

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============handleManualPayment verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['data']['error']['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $this->returnErrorWithResult($result);
			}
		}
		// Check before create sale order EOF
		$orderId = null;
		if ($api) {
			$promo_info = [];
			if(!empty($promo_cms_id)){
				$promotion_errors = null;
				list($promo_cms_id, $promo_rules_id) = $this->processPromoRules($playerId, $promo_cms_id, $amount, $promotion_errors);
				if(!empty($promotion_errors)){
					throw new APIException($promotion_errors, self::CODE_DEPOSIT_REQUEST_OPERATION_FAILED);
				}else{
					$promo_info['promo_cms_id']   = $promo_cms_id;
					$promo_info['promo_rules_id'] = $promo_rules_id;
				}
				$this->utils->debug_log('[handle3rdPartyPayment promo_info]', $promo_info);
			}

			$extra_info_order = [];

			if(!empty($crypto_amount)){
				$validate_crypto = $api->validateCryptoRate($amount, $crypto_amount, true, $currency);

				if(!$validate_crypto['status']){
					throw new APIException($validate_crypto['msg'], self::CODE_EXTERNAL_PAYMENT_API_ERROR);
				}

				$extra_info_order['crypto_amount'] = $crypto_amount;
				$extra_info_order['is_pcf_api'] = true;
			}

			$orderId = $api->createSaleOrder($playerId, $amount, null, $extra_info_order, null, null, null, null, null, $promo_info);
			if ($player_promo_id == '0') {
				$player_promo_id = null;
			}
			if ($bankId == '0') {
				$bankId = null;
			}
			$this->utils->debug_log('[handle3rdPartyPayment]', 'playerId', $playerId, 'player_promo_id', $player_promo_id, 'enabledSecondUrl', $enabledSecondUrl, 'bankId', $bankId, 'orderId', $orderId);

			if($this->utils->getConfig('enable_player_action_trackingevent_system_by_s2s')){
				$extraInfo = [
					'source_url' => $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
					'currency'   => !is_null($currency) ? $currency : $this->utils->getCurrentCurrency()['currency_code'],
					'order_id'   => $orderId,
				];
				$this->utils->playerTrackingEventForS2S($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT', $extraInfo);
			}

			$saleOrder = $this->sale_order->getSaleOrderById($orderId);

			$result = [
				'code' => self::CODE_EXTERNAL_PAYMENT_API_ERROR,
				'data' => [
					'depositId' => $orderId,
					'amount' => $amount,
				],
			];

			$verify_function_list = [
				[ 'name' => 'verifyExistDeposiOrder', 'params' => [$orderId] ],
				[ 'name' => 'verify3rdPartyApiId', 'params' => [$saleOrder, $systemId] ],
				[ 'name' => 'verifyOrderAmount', 'params' => [$saleOrder, $amount] ],
				// [ 'name' => 'verifyDoubleSubmit', 'params' => [$saleOrder, $api] ]
			];

			foreach ($verify_function_list as $method) {
				$this->utils->debug_log('============handle3rdPartyPayment verify_function', $method);
				$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
				// $this->utils->debug_log('============handleManualPayment verify_result', $verify_result, $method['name']);
				$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

				if(!$exec_continue) {
					$result['params'] = ['note' => $verify_result['error_message']];
					$result['errorMessage'] = $verify_result['error_message'];
					return $this->returnErrorWithResult($result);
				}
			}

			$info = $api->getInfoByEnv();
			$enableSecondBool = ($enabledSecondUrl == 'true');

			$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_CREATE_ORDER);
            $respRlt = null;
            $respFileRlt = null;
			$urlSuccess=$this->lockAndTransForPlayerBalance($playerId, function()
				use(&$rlt, $api, $orderId, $playerId, $amount, $player_promo_id, $enableSecondBool, $bankId, $force_assign_reutrn_redirect_type, &$respRlt, &$respFileRlt){
				if(!empty($force_assign_reutrn_redirect_type)) {
					$api->assignDummyRedirectType($force_assign_reutrn_redirect_type);
				}
				$rlt = $api->generatePaymentUrlForm($orderId, $playerId, $amount,
					new DateTime(), $player_promo_id, $enableSecondBool, $bankId);
                
				$success=$rlt && $rlt['success'];
                if (!empty($api->response_result_id)) {
                    //read response results
                    $respRlt = $this->response_result->getResponseCashierResultById($api->response_result_id);
                    $this->utils->debug_log('load failed response', $respRlt);
                }
				return $success;
			});
            if(!$urlSuccess){
                if (!empty($respRlt)) {
                        //create response results again
                        $new_response_result_id = $this->response_result->copyCashierResult($respRlt);
                        $this->response_result->updateCashierResultFilepath($new_response_result_id, $respRlt);
                        $this->utils->debug_log('write back result', $respRlt);
                } else {
                    $this->utils->error_log('lost response result');
                }
            }
			$use_second_url = $api->shouldRedirect(true);
			if(!$use_second_url) {
				$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_SUBMIT_ORDER);
			}

			if ($urlSuccess && $rlt && $rlt['success']) {
				$result['code'] = self::CODE_OK;
				$this->utils->debug_log('generatePaymentUrlForm result', $rlt);
				if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_URL) {
					$result = $this->handleRedirectTypeUrl($rlt, $orderId, $result, $use_second_url);
				} else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_HTML) {
					$result = $this->handleRedirectTypeHtml($rlt, $orderId, $result);
				}else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_FORM) {
					$result = $this->handleRedirectTypeForm($rlt, $orderId, $result);
				// } else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_DIRECT_PAY) {
					// $this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_DIRECT_PAY);
				// 	redirect('/redirect/direct_pay/' . $systemId . '/' . $orderId);
				} else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_QRCODE) {
					$result = $this->handleRedirectTypeQrCode($rlt, $orderId, $result);
				} else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_QRCODE_MODAL){
					$result = $this->handleRedirectTypeQrCodeModal($rlt, $orderId, $result);
				} else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_STATIC) {
					$result = $this->handleRedirectTypeStatic($rlt, $orderId, $result, $systemId);
				}
			} else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_ERROR) {
				$result = $this->handleRedirectTypeError($rlt, $orderId, $result);
			} else if ($rlt['type'] == Abstract_payment_api::REDIRECT_TYPE_ERROR_MODAL) {
				$result = $this->handleRedirectTypeErrorModal($rlt, $orderId, $result);
			}
			return $result;
		}
		else {
			$result = [];
			$rlt = [];
			$result['cooe'] = self::CODE_PAYMENT_METHOD_NOT_FOUND;
			// $result['responseEnum'] = 'Payyment API class not found';
			return $this->handleRedirectTypeError($rlt, $orderId, $result);
			// $this->returnErrorWithResult($result);
		}
		// $this->returnBadRequest();
	}

/*=============================== handle 3rd party payment redirect type start =========================*/
	private function handleRedirectTypeUrl($rlt, $orderId,  $result, $use_second_url) {
		if(!$use_second_url) {
			$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_URL);
		}
		if (!empty($rlt['url'])) {
			if(!empty($rlt['disableIframe']) && $rlt['disableIframe']){ // disable iframe
				$result['data']['actionType'] = 'REDIRECT_PAGE_DIRECT';
				$result['data']['redirectPageDirect']['url'] = $rlt['url'];
			}else{
				$result['data']['actionType'] = 'REDIRECT_PAGE';
				$result['data']['redirectPage']['url'] = $rlt['url'];
			}
			// $result['redirectHttpMethod'] = 'GET';
			// $result['redirectUrl'] = $rlt['url'];
			// $result['redirectType'] = $this->playerapi_lib->matchOutputRedirectTypeStatus($rlt['type']);
			// $result['responseEnum'] = 'OK';
			// $result['success'] = true;

			$this->utils->debug_log('==========handleRedirectTypeUrl from api rlt', $rlt);
			$this->utils->debug_log('==========handleRedirectTypeUrl result', $result);
			return $this->returnSuccessWithResult($result);
		} else {
			// $result['responseEnum'] = 'ACTION_UNSUCCESSFUL';
			return $this->handleRedirectTypeError($rlt, $orderId, $result);
		}
	}

	private function handleRedirectTypeHtml($rlt, $orderId, $result) {
        $this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_FORM);

		if (!empty($rlt['html'])) {
			$result['data']['actionType'] = 'HTML';
			$result['data']['html']['content'] = $rlt['html'];
			// $result['raw_html'] = $rlt['html'];
			return $this->returnSuccessWithResult($result);
		} else {
			// $result['responseEnum'] = 'ACTION_UNSUCCESSFUL';
			return $this->handleRedirectTypeError($rlt, $orderId, $result);
		}
	}

	private function handleRedirectTypeForm($rlt, $orderId, $result) {
		$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_FORM);

		$result['data']['actionType'] = 'SUBMIT_POST_FORM';
		$result['data']['submitPostForm']['url'] = $rlt['url'];
		$result['data']['submitPostForm']['parameters'] = $rlt['params'];
		// $result['redirectHttpMethod'] = (isset($urlForm['post'])) ? ($urlForm['post'] == true ? 'POST' : 'GET') : 'GET';
		// $result['redirectUrl'] = $rlt['url'];
		// $result['redirectType'] = $this->playerapi_lib->matchOutputRedirectTypeStatus($rlt['type']);
		// $result['redirectParams'] = $rlt['params'];
		// $result['responseEnum'] = 'OK';
		// $result['success'] = true;
		$this->utils->debug_log('==========handleRedirectTypeForm from api rlt', $rlt);
		$this->utils->debug_log('==========handleRedirectTypeForm result', $result);
		return $this->returnSuccessWithResult($result);
	}

	private function handleRedirectTypeQrCode($rlt, $orderId, $result) {
		$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_QRCODE);

		if (!empty($rlt['url']) || !empty($rlt['base64_url']) || !empty($rlt['base64']) || !empty($rlt['image_url'])) {
			$result['data']['actionType'] = 'SCAN_QR';

			if(!empty($rlt['url'])) {
				$result['data']['scanQr']['qrType'] = 'link';
				$result['data']['scanQr']['link'] = $rlt['url'];
				// $result['qrcodeImgUrl'] = $rlt['url'];
				// $result['qrcodeType'] = 'url';
			}
			if(!empty($rlt['base64_url'])) {
				$result['data']['scanQr']['qrType'] = 'base64Image';
				$result['data']['scanQr']['base64Image'] = $rlt['base64_url'];
				// $result['qrcodeImgUrl'] = $rlt['base64_url'];
				// $result['qrcodeType'] = 'base64_url';
			}
			if(!empty($rlt['base64'])) {
				$result['data']['scanQr']['qrType'] = 'base64Content';
				$result['data']['scanQr']['base64Content'] = $rlt['base64'];
				// $result['qrcodeImgUrl'] = $rlt['base64'];
				// $result['qrcodeType'] = 'base64';
			}
			if(!empty($rlt['image_url'])) {
				$result['data']['scanQr']['qrType'] = 'base64Content';
				$result['data']['scanQr']['base64Image'] = $rlt['base64'];
				// $result['qrcodeImgUrl'] = $rlt['base64'];
				// $result['qrcodeType'] = 'image_url';
			}
			if(!empty($rlt['status_url'])) { # URL used to poll for payment status
				$result['data']['scanQr']['qrType'] = 'link';
				$result['data']['scanQr']['link'] = $rlt['status_url'];
				// $result['qrcodeImgUrl'] = $rlt['status_url'];
				// $result['qrcodeStatusSuccessKey'] = $rlt['status_success_key'];
				// $result['qrcodeType'] = 'status_url';
			}
			if(!empty($rlt['logoUrl'])) {
				$result['data']['scanQr']['logoUrl'] = $rlt['logoUrl'];
			}

			// $result['redirectType'] = $this->playerapi_lib->matchOutputRedirectTypeStatus($rlt['type']);
			// $result['responseEnum'] = 'OK';
			// $result['success'] = true;
			// if (!empty($rlt['cust_payment_data'])){
			// 	$result['qrcodeCustPaymentData'] = $rlt['cust_payment_data'];
			// 	if(!empty($rlt['cust_hide_copy_button_of_payment_data_index'])){
			// 		$result['custHideCopyButtonOfPaymentDataIndex'] = $rlt['cust_hide_copy_button_of_payment_data_index'];
			// 	}
			// }

			// if($api->getSystemInfo('qrcode_upper_msg')) {
			// 	$result['qrcodeUpperMsg'] = $api->getSystemInfo('qrcode_upper_msg');
			// }
			// if($api->getSystemInfo('qrcode_lower_msg')) {
			// 	if(is_array($api->getSystemInfo('qrcode_lower_msg'))){
			// 		foreach ($api->getSystemInfo('qrcode_lower_msg') as $lang => $value) {
			// 			if($this->language_function->getCurrentLanguageName() == $lang){
			// 				$result['qrcodeLowerMsg'] = $value;
			// 			}
			// 		}
			// 	}else{
			// 		$result['qrcodeLowerMsg'] = $api->getSystemInfo('qrcode_lower_msg');
			// 	}
			// }
			return $this->returnSuccessWithResult($result);
		}
		else {
			// $result['responseEnum'] = 'ACTION_UNSUCCESSFUL';
			return $this->handleRedirectTypeError($rlt, $orderId, $result);
		}
	}

	private function handleRedirectTypeQrCodeModal($rlt, $orderId, $result) {
		$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_QRCODE_MODAL);
		return $this->handleRedirectTypeQrCode($rlt, $orderId, $result);
	}

	private function handleRedirectTypeStatic($rlt, $orderId, $result, $systemId) {
		$this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_STATIC);

		# This type displays data, an array of key=>value, in a table
		if (!empty($rlt['data'])) {
			$result['data']['actionType'] = 'DISPLAY';
			$result['data']['display'] = $this->depositApiOutput($rlt['data'], $systemId);;
			// $result['staticData'] = $rlt['data'];
			// if(isset($rlt['style_data'])) {
			// 	$result['staticStyleData']=$rlt['style_data'];
			// }
			// if(isset($rlt['getExternalApi_btn'])) {
			// 	$result['staticGetExternalApiBtn']=$rlt['getExternalApi_btn'];
			// }
			// if(isset($rlt['setExternalApi_btn'])) {
			// 	$result['staticSetExternalApiBtn']=$rlt['setExternalApi_btn'];
			// }
			// $result['hide_timeout']= @isset($rlt['hide_timeout'])?$rlt['hide_timeout']:false;
			// $result['hide_system_confirmation']= @isset($rlt['hide_system_confirmation'])?$rlt['hide_system_confirmation']:'';
			// $result['player_bank_info']= @isset($rlt['player_bank_info'])?$rlt['player_bank_info']:'';
			// $result['collection_text_transfer'] = @isset($rlt['collection_text_transfer'])?$rlt['collection_text_transfer']:'';
			// $result['is_not_display_recharge_instructions']=@isset($rlt['is_not_display_recharge_instructions'])?$rlt['is_not_display_recharge_instructions']:'';
			// $result['systemId'] = $systemId;
			// $result['responseEnum'] = 'OK';
			// $result['success'] = true;
			return $this->returnSuccessWithResult($result);
		}
		else {
			// $result['responseEnum'] = 'ACTION_UNSUCCESSFUL';
			return $this->handleRedirectTypeError($rlt, $orderId, $result);
		}
	}

	private function handleRedirectTypeError($rlt, $orderId, $result) {
        $this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_ERROR);
		$result['errorMessage'] = isset($rlt['message']) ? $rlt['message'] : lang('Invalidte API response');
		$result['data']['actionType'] = 'ERROR';
		// $result['data']['error']['message'] = isset($rlt['message']) ? $rlt['message'] : lang('Invalidte API response');
		// $result['redirectType'] = $this->playerapi_lib->matchOutputRedirectTypeStatus($rlt['type']);
		// $result['message'] = $rlt['message'];
		// $result['responseEnum'] = isset($rlt['new_player_center_api_error_type']) ? $rlt['new_player_center_api_error_type'] : $result['responseEnum'];

		return $this->returnErrorWithResult($result);
	}

	private function handleRedirectTypeErrorModal($rlt, $orderId, $result) {
        $this->sale_orders_status_history->createSaleOrderStatusHistory($orderId,Sale_order::DEPOSIT_STATUS_RERIERCT_ERROR_MODAL);
        // $result['responseEnum'] = 'ACTION_UNSUCCESSFUL';
		return $this->handleRedirectTypeError($rlt, $orderId, $result);
	}

	/*=============================== handle 3rd party payment redirect type end =========================*/

	private function handleManualPayment($amount, $player_id, $player_bank_details_id, $payment_account_id, $promo_cms_id) {
		$this->utils->debug_log('============handleManualPayment start');

		$depositAmount = $amount;
		$player_bank_detail = $this->playerbankdetails->getBankDetailsById($player_bank_details_id);
		$player_bank_type_id = !empty($player_bank_detail)? $player_bank_detail['bankTypeId'] : '';
		$playerId = $player_id;
		$defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];
		$secure_id = null;
		$deposit_time = null;
		$deposit_time_out = null;
		$depositor_name= null;
		// if ($this->utils->isEnabledFeature('enable_manual_deposit_input_depositor_name')) {
			// $depositor_name= $this->input->post("depositor_name");
		// }

		$dwIp = $this->input->ip_address();
		$geolocation = $this->utils->getGeoplugin($dwIp);
		$player_promo_id =null;
		$sub_wallet_id = null;
		$group_level_id = null;
		$depositDatetime = null;
		$mode_of_deposit = null;
		$depositReferenceNo = null;
		$pendingDepositWalletType  = null;
		$promo_info = [];
		$depositAccountNo = null;

		// if ($this->utils->isEnabledFeature('enable_deposit_upload_documents')) {
		// 	$file1 = isset($_FILES['file1']) ? $_FILES['file1'] : null;
		// 	$file2 = isset($_FILES['file2']) ? $_FILES['file2'] : null;
		// 	$this->utils->debug_log('=========== upload attached document exist ? file1 [ '.json_encode($file1).' ] , file2 [ '.json_encode($file2).' ] ');
		// }

		$payment_account = $this->payment_account->getPaymentAccountWithVIPRule($payment_account_id, $playerId);

		$result = [
			'code' => self::CODE_DEPOSIT_REQUEST_OPERATION_FAILED,
			'data' => [
				'actionType' => 'MANUAL',
				'manual' => [
					'success' => false
				],
			],
		];

		$verify_function_list = [
			[ 'name' => 'verifyManualDepositCoolDownTime', 'params' => [$player_id] ],
			[ 'name' => 'verifyResponsibleGamingDepositLimits', 'params' => [$playerId, $depositAmount] ],
			[ 'name' => 'verifyExistsUnfinishedManualDeposit', 'params' => [$player_id] ],
			[ 'name' => 'verifyPaymentAccountForbidden', 'params' => [$payment_account, $player_id] ],
			[ 'name' => 'verifyDepositAmountLessThanMin', 'params' => [$payment_account, $depositAmount] ],
			[ 'name' => 'verifyDepositAmountGreaterThanMax', 'params' => [$payment_account, $depositAmount] ],
			[ 'name' => 'verifyAvailableBankTypeOnDeposit', 'params' => [$player_bank_type_id] ],
			[ 'name' => 'verifyPaymentAccountAvailable', 'params' => [$payment_account_id] ],
			[ 'name' => 'verifyAvailableBankTypeOnDeposit', 'params' => [$payment_account->payment_type_id] ],
			// [ 'name' => 'verifyUploadDocuments', 'params' => [$file1, $file2] ]
		];

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============handleManualPayment verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============handleManualPayment verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['data']['manual']['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $result;
			}
		}

		if(!empty($promo_cms_id)){
			$promotion_errors = null;
			list($promo_cms_id, $promo_rules_id) = $this->processPromoRules($playerId, $promo_cms_id, $depositAmount, $promotion_errors, $sub_wallet_id);
			if(!empty($promotion_errors)){
				throw new APIException($promotion_errors, self::CODE_DEPOSIT_REQUEST_OPERATION_FAILED);
			}else{
				$promo_info['promo_cms_id'] = $promo_cms_id;
				$promo_info['promo_rules_id'] = $promo_rules_id;
			}
		}
		// $payment_account_data = $this->_displayPaymentAccountData($payment_account, TRUE);
		try{
			$deposit_order_args = [
				Sale_order::PAYMENT_KIND_DEPOSIT,
				$payment_account_id,
				$playerId,
				$depositAmount,
				$defaultCurrency,
				$player_promo_id,
				$dwIp,
				$geolocation['geoplugin_city'] . ',' . $geolocation['geoplugin_countryName'],
				$player_bank_details_id,
				null,
				null,
				null,
				Sale_order::STATUS_PROCESSING,
				$sub_wallet_id,
				$group_level_id,
				$depositDatetime,
				$depositReferenceNo,
				$pendingDepositWalletType,
				null,
				$this->utils->is_mobile(),
				$this->utils->getNowForMysql(),
				$promo_info,
				$depositor_name,
				$depositAccountNo,
				Sale_order::PLAYER_DEPOSIT_METHOD_UNSPECIFIED,
				$secure_id,
				$deposit_time,
				$deposit_time_out,
				$mode_of_deposit
			];

			$msg = lang('Thank you for your deposit Please check back again later.');
			$saleOrder = call_user_func_array([$this->sale_order, 'createDepositOrder'], $deposit_order_args);

			$result['code'] = self::CODE_OK;
			$result['data']['depositId'] = $saleOrder['id'];
			$result['data']['amount'] = $depositAmount;
			$result['data']['manual']['success'] = true;
			$result['data']['manual']['message'] = $msg;
			// $result['success'] = true;
			// $result['params'] = ['amount' => $depositAmount, 'depositRequestId' => $saleOrder['id'], 'note' => $msg];
			// $result['message'] = $msg;
			// $result['responseEnum'] = '';

			// $cryptoQty = $this->input->post('cryptoQty');
			// $request_cryptocurrency_rate = $this->input->post('request_cryptocurrency_rate');
			// $this->processCryptoCurrency($payment_account);

			// $this->process3rdPartyApiWhenManualDeposit();

			if($this->utils->getConfig('enable_fast_track_integration')) {
				$this->load->library('fast_track');
				$this->fast_track->requestDeposit($saleOrder);
			}
			return $result;
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex);
			$result['data']['manual']['message'] = lang('notify.39');
			$result['errorMessage'] = lang('notify.39');
			return $result;
		}
	}

	private function handleWithdrawalrequest($bank_details_id, $bank_account_number, $bank_account_full_name, $amount, $bank_branch, $withdrawal_password, $crypto_amount, $currency) {
		$this->utils->debug_log('============handleWithdrawalrequest start', $bank_details_id, $bank_account_number, $bank_account_full_name, $amount, $bank_branch, $withdrawal_password, $crypto_amount);

		$result = [
			'code' => self::CODE_WITHDRAW_REQUEST_OPERATION_FAILED,
		];

		$this->load->model(['playerbankdetails','risk_score_model', 'banktype', 'player_promo', 'transactions', 'users', 'wallet_model']);

		$playerId = $this->player_id;
		// $player = $this->load->get_var('player');
		$player = $this->player_model->getPlayerArrayById($playerId);
		$this->utils->debug_log('verify playerId', $playerId, 'player', $player, 'input post', $this->input->post());
		// $type = !empty($request_body['withdrawal_bank_type']) ? $request_body['withdrawal_bank_type'] : 'bank';
		// $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank_type_id, $bank_account_number);
		$playerBankDetails = $this->playerbankdetails->getBankDetailsById($bank_details_id);
		$this->utils->debug_log('verify playerBankDetails', $playerBankDetails);

		if(empty($playerBankDetails)){
			$result['errorMessage'] = lang('bank account not found');
			return $result;
		}

		if($playerBankDetails['bankDetailStatus']!= Playerbankdetails::STATUS_ACTIVE){
			$result['errorMessage'] = lang('bank account not active');
			return $result;
		}

		$bankDetailsId = $playerBankDetails['playerBankDetailsId'];
		$bankType = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId']);
		$bankCode = !empty($bankType->bank_code) ? $bankType->bank_code : '';

		$verify_function_list = [
			[ 'name' => 'verifyAgencyCreditMode', 'params' => [$player] ],
			// [ 'name' => 'verifyBankTypeExist', 'params' => [$type] ],
			[ 'name' => 'verifyEnablePlayerWithdrawal', 'params' => [$player] ],
            [ 'name' => 'verifyPlayerCompleteUserinfo', 'params' => [$playerId] ],
			[ 'name' => 'verifyKycRiskWithdrawalStatus', 'params' => [$playerId] ],
			[ 'name' => 'verifySubwalletNegativeBalance', 'params' => [$playerId] ],
			[ 'name' => 'verifyExistBankDetailsId', 'params' => [$bankDetailsId] ],
			[ 'name' => 'verifyWithdrawalPassword', 'params' => [$playerId, $withdrawal_password] ],
			[ 'name' => 'verifyAmountDecimal', 'params' => [$amount] ],
			[ 'name' => 'verifyBankDetailsBelongToPlayer', 'params' => [$playerId, $bankDetailsId] ],
			// [ 'name' => 'verifyBankDetailsAccountValide', 'params' => [$playerId, $bankDetailsId] ],
			[ 'name' => 'verifyPlayerWithdrawalRule', 'params' => [$playerId, $amount] ],
			[ 'name' => 'verifyUnfinishedWithdrawCondition', 'params' => [$playerId] ],
			[ 'name' => 'verifyLastWithdrawalRequestDone', 'params' => [$playerId] ],
			// [ 'name' => 'verifyEmptybankTypeId', 'params' => [$bankDetailsId, $bank_type_id] ],
			[ 'name' => 'verifyPlayerExistRealName', 'params' => [$playerId] ],
		];

		$enabledWithdrawaFee = ($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId));
		$enabledWithdrawaBankFee = ($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId));
		$withdrawFeeAmount = 0;
		$withdrawBankFeeAmount = 0;
		$calculationFormula = '';
		$calculationFormulaBank = '';

		if($enabledWithdrawaFee){
			list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player['levelId'], $amount);
			$verify_function_list[] = [ 'name' => 'verifyWithdrawalFeeAmountOverMainAmount', 'params' => [$playerId, $amount, $withdrawFeeAmount] ];
		}

		if($enabledWithdrawaBankFee){
			list($withdrawBankFeeAmount,$calculationFormulaBank) = $this->payment_library->calculationWithdrawalBankFee($playerId, $bankCode, $amount);
			$verify_function_list[] = [ 'name' => 'verifyWithdrawalBankFeeAmountOverMainAmount', 'params' => [$playerId, $amount, $withdrawBankFeeAmount, $withdrawFeeAmount] ];
		}

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============handleWithdrawalrequest verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============handleManualPayment verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['errorMessage'] = $verify_result['error_message'];
				return $result;
			}
		}

		# All checks done, we can now proceed to submit withdrawal request
		## Save bank info if it's new (no bankDetailsId given)
		// $bankDetailsId = $this->addNewBankDetails($bankDetailsId, $playerId, $bank_type_id, $bank_account_number, $bank_account_full_name, $bank_branch);
		$walletAccountData = [];
		$walletAccountData = $this->buildInitwalletAccountDataArray($walletAccountData, $playerId, $bankDetailsId, $player, $amount, $withdrawFeeAmount, $withdrawBankFeeAmount);
		$walletAccountData = $this->resetDwStatusPendingReivew($walletAccountData, $playerId);
		$walletAccountData = $this->resetDwStatusPendingReivewRiskScore($walletAccountData, $playerId);

		$this->utils->debug_log('-----------------bankType', $bankType);
		if($this->utils->isCryptoCurrency($bankType)){
			$cryptoRes = call_user_func_array([$this, 'processCryptoCurrencyWithdrawal'], [$amount, $crypto_amount, $bankType, $currency]);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($cryptoRes);

			if(!$exec_continue) {
				$result['errorMessage'] = $cryptoRes['error_message'];
				return $result;
			}

			$crypto = $crypto_amount;
			$rate = $cryptoRes['data']['rate'];
			$cryptocurrency = $cryptoRes['data']['cryptocurrency'];
			$player_rate = $cryptoRes['data']['player_rate'];
			$withdrawal_notes = 'Wallet Address: '.$walletAccountData['bankAccountNumber'].' | '.$cryptocurrency.': '.$crypto.' | Crypto Real Rate: '.$rate;
            $walletAccountData['notes'] = $withdrawal_notes;
            $walletAccountData['extra_info'] = $crypto;
		}

		//transfer back balance by feature
		$rlt= $this->_transferBackToMainWallet($playerId, $player['username']);
		$this->utils->debug_log('after _transferBackToMainWallet, result', $rlt);
		if(!$rlt){
			$result['errorMessage'] = lang('Transfer Failed From Sub-wallet');
			return $result;
		}

		$walletAccountData['amount']=$amount;
		$beforeBalance = $this->wallet_model->getMainWalletBalance($playerId);//$this->wallet_model->getTargetTransferBalanceOnMainWallet($playerId, Wallet_model::MAIN_WALLET_ID);
		$walletAccountData['before_balance']=$beforeBalance;
		$walletAccountData['after_balance']=$beforeBalance - $amount;

		$this->load->model(array('wallet_model', 'transaction_notes', 'users', 'walletaccount_timelog', 'walletaccount_notes'));
		$walletModel = $this->wallet_model;
		$hasSufficientBalance = false;
		$errorMsg = '';
		$cryptoSet = isset($crypto) ? $crypto : false;
		$rate = isset($rate) ? $rate : '1';
		$cryptocurrency = isset($cryptocurrency) ? $cryptocurrency : false;
		$playerRateSet = isset($player_rate) ? $player_rate : false;
		$fee = isset($fee) ? $fee : false;
		$withdrawal_notes = isset($withdrawal_notes) ? $withdrawal_notes : false;
		if(($cryptoSet != false) && ($playerRateSet != false)) {
			$walletAccountData['showNotesFlag'] = '1';
		}

		$success = $this->lockAndTransForPlayerBalance($playerId, function () use ($walletModel, $playerId, $walletAccountData, $fee, $cryptoSet, $cryptocurrency, $rate, $playerRateSet, &$walletAccountId, &$hasSufficientBalance, &$errorMsg, $calculationFormula, $withdrawFeeAmount,$withdrawal_notes, $calculationFormulaBank, $withdrawBankFeeAmount, $enabledWithdrawaFee, $enabledWithdrawaBankFee) {

			## Check main wallet (id = 0) balance
			$hasSufficientBalance = $this->utils->checkTargetBalance($playerId, 0, $walletAccountData['amount'], $errorMsg);
			if($hasSufficientBalance) {
				$localBankWithdrawalDetails = array(
					'withdrawalAmount' => $walletAccountData['amount'],
					'playerBankDetailsId' => $walletAccountData['player_bank_details_id'],
					'depositDateTime' => $walletAccountData['dwDateTime'],
					'status' => 'active',
				);
				$walletAccountId = $walletModel->newWithdrawal($walletAccountData, $localBankWithdrawalDetails, $playerId);
				$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from PCF API, status => request");

				if($enabledWithdrawaFee){
					$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from PCF API, status => request ; %s ; Withdrawal Fee Amount is %s", $calculationFormula, $withdrawFeeAmount);
				}

				if($enabledWithdrawaBankFee){
					if ($withdrawBankFeeAmount > 0) {
						$withdrawalActionLogByPlayer = $withdrawalActionLogByPlayer . ' ' . $calculationFormulaBank;
					}
				}

				$walletModel->addWalletaccountNotes($walletAccountId, $playerId, $withdrawalActionLogByPlayer, $walletAccountData['dwStatus'], null, Walletaccount_timelog::PLAYER_USER);

				if(!empty($walletAccountId) && ($cryptoSet != false) && ($playerRateSet != false)) {
					$adminUserId = $this->users->getSuperAdminId();
					$lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
					$crypto_withdrawal_order_id = $walletModel->createCryptoWithdrawalOrder($walletAccountId, $cryptoSet, $rate, $this->utils->getNowForMysql(), $this->utils->getNowForMysql(),$cryptocurrency);
					$this->utils->debug_log('============handleWithdrawalrequest crypto_withdrawal_order_id', $crypto_withdrawal_order_id);
				}
			}
			return !empty($walletAccountId);
		});

		$this->processAutomationRiskPreChecker($walletAccountId, $playerId);

		$this->utils->debug_log("Withdrawal submitted by player [$playerId] evaluated, amount [$amount], hasSufficientBalance [$hasSufficientBalance], walletAccountId [$walletAccountId], errorMsg [$errorMsg]");
		$verify_result = $this->verifyHasSufficientBalance($hasSufficientBalance, $errorMsg);
		$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

		$this->utils->debug_log('============handleWithdrawalrequest verify_result', 'verifyHasSufficientBalance', $verify_result, 'exec_continue', $exec_continue);

		if(!$exec_continue) {
			$result['errorMessage'] = $verify_result['error_message'];
			return $result;
		}

		$this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);

		if(!$success) {
			$result['errorMessage'] = lang('error.withdrawal_failed');
			return $result;
		}

		$walletAccountData['walletAccountId'] = $walletAccountId;
		if($this->utils->getConfig('enable_fast_track_integration')) {
			$this->load->library('fast_track');
			$this->fast_track->requestWithdraw($walletAccountData);
		}

		if($this->utils->getConfig('enable_player_action_trackingevent_system_by_s2s')){
			if($success){
				$extraInfo = [
					'source_url'      => $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
					'currency'        => !is_null($currency) ? $currency : $this->utils->getCurrentCurrency()['currency_code'],
					'walletAccountId' => $walletAccountId,
				];
				$this->utils->playerTrackingEventForS2S($playerId, 'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL', $extraInfo);
			}
		}

		#OGP-22453,22538
		if ($this->utils->getConfig('enabled_withdrawal_abnormal_notification')) {
			if($success){
				$userId = $this->users->getSuperAdminId();
				$this->triggerWithdrawalEvent($playerId, $walletAccountId, null, null, $userId);
			}
		}

		$result['code'] = self::CODE_OK;
		$result['successMessage'] = lang($this->config->item('playercenter.withdrawMsg.success') ?: 'notify.58');
		return $result;
	}

	private function handleDepositFileUpload($deposit_id, $image) {
		$this->utils->debug_log('============handleDepositFileUpload start');
		$result = [
			'code' => self::CODE_DEPOSIT_UPLOAD_FAILED,
		];

		$verify_function_list = [
			[ 'name' => 'verifyExistDeposiOrder', 'params' => [$deposit_id] ],
			[ 'name' => 'verifyEnableUploadDepositFile', 'params' => []],
			// [ 'name' => 'verifyEmptyUploadFile', 'params' => [$image] ],
		];

		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============postDepositUpload verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			// $this->utils->debug_log('============postDepositUpload verify_result', $verify_result, $method['name']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);

			if(!$exec_continue) {
				$result['data']['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $result;
			}
		}

		$sale_order = $this->sale_order->getSaleOrderById($deposit_id);
		$player_id = $sale_order->player_id;

		$this->load->model(['player_attached_proof_file_model']);
		$input = array(
			"player_id"       => $player_id,
			"tag"             => player_attached_proof_file_model::Deposit_Attached_Document,
			"sales_order_id"  => $sale_order->id
		);

		$dataset = [
			'input' => $input ,
			'image' => $image
		];
		$upload_resp = $this->player_attached_proof_file_model->upload_deposit_receipt($dataset);
		$this->comapi_log("=====================".__METHOD__, 'upload_resp', $upload_resp);

		if ($upload_resp['msg_type'] == BaseController::MESSAGE_TYPE_ERROR) {
			// $ret_data = $upload_resp['msg'];
			// throw new Exception(lang("Error uploading file"), self::CODE_MDU_ERROR_UPLOADING_FILE);
			$result['data']['message'] = lang("Error uploading file").': '.$upload_resp['msg'];
			$result['errorMessage'] = lang("Error uploading file").': '.$upload_resp['msg'];
		}
		else {
			$result['code'] = self::CODE_OK;
			$result['data']['message'] = lang('Attachment uploaded successfully');
		}
		return $result;
	}
	private function getPaymentSettingsByPlayerId($player_id, $type = 1) {
		try {
			$result = [];
			$playerWithdrawalRule = $this->utils->getWithdrawMinMax($player_id);
			$uploadFileMaxSize = $this->utils->getMaxUploadSizeByte();
			$useBranchAsIfscInWithdrawalAccounts = $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts');

			$this->CI->load->model(['financial_account_setting']);
			$financial_account_rule = $this->financial_account_setting->getPlayerFinancialAccountRulesByPaymentAccountFlag($type);
			$fieldShowValue = explode(',', $financial_account_rule['field_show']);

			$enabledAmountNote = $this->utils->getConfig('custom_withdrawal_amount_note');
			$enabledTimesLimit = $this->utils->getConfig('withdraw_times_limit');
			$enabledVipWithdrawalFee = $this->utils->getConfig('calculate_withdrawal_fee_based_on_vip_level');
			$enabledWithdrawalBankFee = $this->utils->getConfig('enable_withdrawl_bank_fee');
			$ebabledWithdrawalBankFeeNote = $this->utils->getConfig('player_center_withdrawal_page_fee_hint');

			if (!empty($enabledVipWithdrawalFee)) {
				list($freeMonthlyWithdrawalAmount, $withdrawalFeePercentage) = $this->getWithdrawalFeeSettings($player_id, $enabledVipWithdrawalFee, $playerWithdrawalRule['max_monthly_withdrawal']);
			}

			if (!empty($enabledWithdrawalBankFee)) {
				$withdrawalBankFeeDetails = $this->getWithdrawalBankFeeSettings($enabledWithdrawalBankFee);
			}

			$withdrawal_result = [
				'minPerTrans' => (double)$playerWithdrawalRule['min_withdraw_per_transaction'],
				'maxPerTrans' => (double)$playerWithdrawalRule['max_withdraw_per_transaction'],
				'dailyLimit'  => (double)$playerWithdrawalRule['daily_max_withdraw_amount'],
				'monthlyTotal' => isset($monthlyWithdrawalTotal) ? (float)$monthlyWithdrawalTotal : 0,
				'dailyTimesLimit' => $enabledTimesLimit ? $playerWithdrawalRule['withdraw_times_limit'] : 0,
				'feePercentage' => isset($withdrawalFeePercentage) ? (float)$withdrawalFeePercentage : 0,
				'freeMonthlyLimit' => isset($freeMonthlyWithdrawalAmount) ? (float)$freeMonthlyWithdrawalAmount : 0,
				'customReminder' => $enabledAmountNote ? lang('Withdrawal Amount Note') : '',
				'bankFeeReminder' => $ebabledWithdrawalBankFeeNote,
				'bankFee' => isset($withdrawalBankFeeDetails) ? $withdrawalBankFeeDetails : []
			];
			$withdrawal_result = $this->playerapi_lib->convertOutputFormat($withdrawal_result);

			$deposit_result = [
				'uploadFileMaxSize' => $uploadFileMaxSize
			];
			$bankAccount_result = [
				"enabledIfscCode" => $useBranchAsIfscInWithdrawalAccounts,
				"enabledPhoneNumber" => in_array(Financial_account_setting::FIELD_PHONE, $fieldShowValue),
				"enabledProvince" => in_array(Financial_account_setting::FIELD_BANK_AREA, $fieldShowValue),
				"enabledBankBranch" => in_array(Financial_account_setting::FIELD_BANK_BRANCH, $fieldShowValue)
			];

			$result['withdrawal'] =  $withdrawal_result;
			$result['deposit'] =  $deposit_result;
			$result['bankAccount'] =  $bankAccount_result;

			if(!empty($result)){
				$output['code'] = self::CODE_OK;
				$output['data'] = $result;
				return $this->returnSuccessWithResult($output);
			}
		}
		catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);

			return $this->returnErrorWithResult($result);
		}
	}
}
