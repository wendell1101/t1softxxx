<?php
require_once 'AjaxBaseController.php';

/**
 * Provides deposit ajax function
 */
class Deposit extends AjaxBaseController {
	public function __construct(){
		parent::__construct();

		$this->load->model(array('payment_account'));
	}

    public function _remap($method){
        global $CI, $URI;
        if(!$this->load->get_var('isLogged')){
            return $this->returnJsonResult(array('status' => 'failed', 'msg' => lang('Not Login')));
        }

        $method = strtolower($this->input->server('REQUEST_METHOD')) . ucfirst($method);
        if(!method_exists($CI, $method)){
            return show_404();
        }

		return call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
    }

	protected function _process_promo_rules($playerId, $promo_cms_id, $transferAmount, &$error=null, $subWalletId=null){
		$this->load->model(['promorules']);

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

    protected function _displayPaymentAccountData($payment_account, $has_detail = FALSE){
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
            if(!empty($payment_account->extra_info)){
                $extra_info = json_decode($payment_account->extra_info, true);
                $payment_account_data['qrcode_content'] = $extra_info['qrcode_content'];
            }else{
                $payment_account_data['qrcode_content'] = '';
            }
            $payment_account_data['payment_account_number'] = $payment_account->payment_account_number;
            $payment_account_data['payment_account_name'] = $payment_account->payment_account_name;
            $payment_account_data['payment_branch_name'] = $payment_account->payment_branch_name;
            $payment_account_data['min_deposit_trans'] = $payment_account->vip_rule_min_deposit_trans;
            $payment_account_data['max_deposit_trans'] = $payment_account->vip_rule_max_deposit_trans;
        }

        return $payment_account_data;
    }

    public function getPaymentAccountDetail($payment_account_id){
        $playerId = $this->load->get_var('playerId');
		$payment_account = $this->payment_account->getPaymentAccountWithVIPRule($payment_account_id, $playerId);

		if (empty($payment_account)) {
            return $this->returnJsonResult(array('status' => 'error', 'msg' => sprintf(lang('gen.error.forbidden'), lang('pay.depmethod'))));
		}

        $result = array('status' => 'valid');

        $result['payment_account_data'] = $this->_displayPaymentAccountData($payment_account, TRUE);

        return $this->returnJsonResult($result);
    }


    public function postManualDeposit(){
        $this->utils->debug_log('--- postManualDeposit --- input post', $this->input->post());
        $depositAmount = $this->input->post('depositAmount');
        $payment_account_id = $this->input->post('payment_account_id');
        $playerBankDetailsId = $this->input->post('playerBankDetailsId');
        $deposit_notes = $this->input->post('deposit_notes');
        $playerId = $this->authentication->getPlayerId();
        $defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];
        $secure_id = $this->input->post("secure_id");
        $deposit_time = $this->input->post("deposit_time");
        $deposit_time_out = $this->input->post("deposit_time_out");
        $depositor_name= null;
        if ($this->utils->isEnabledFeature('enable_manual_deposit_input_depositor_name')) {
            $depositor_name= $this->input->post("depositor_name");
        }
        
        $postData = array(
            'amount'         => $depositAmount,
        );

        $promo_data = $this->promorules->getPromoruleByPromoCms($this->input->post('promo_cms_id'));
        if(!empty($this->input->post('promo_cms_id'))){
            $postData['selected_promo_id'] = $this->input->post('promo_cms_id');
            $postData['selected_promo_name'] = $promo_data['promoName'];
        }

        #check the format of secure_id
        $check_secure_id = false;
        if($secure_id && $secure_id !== null) {
            $prefix = 'D';
            $secure_id_default_length = 12;
            if(strpos($secure_id,$prefix) === 0) {
                if(!$this->payment_account->checkSecureidExists($secure_id)){

                    $random_length = $this->utils->isEnabledFeature('enable_change_deposit_transaction_ID_start_with_date')
                    ? (strlen($prefix) + strlen(date('Ymd')) + $this->utils->getConfig('get_secureid_random_length'))
                    : (strlen($prefix) + $secure_id_default_length);

                    if (strlen($secure_id)== $random_length) {
                        $check_secure_id = true;
                    }
                }
            }
            if (!$check_secure_id) {
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return $this->returnJsonResult(array('status' => 'failed', 'msg' => lang('notify.wrong_order_number')));
            }
        }

        $dwIp = $this->input->ip_address();
        $geolocation = $this->utils->getGeoplugin($dwIp);
        $player_promo_id =null;
        $sub_wallet_id = $this->input->post("wallet_id");
        $group_level_id = null;
        $depositDatetime = $this->input->post("deposit_datetime");
        $mode_of_deposit = $this->input->post("mode_of_deposit");
        $depositReferenceNo = null;
        $pendingDepositWalletType  = null;

        $promo_cms_id = $this->input->post('promo_cms_id');

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
                
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
            }
        }

        $this->load->model(array('sale_order', 'banktype', 'sale_orders_notes', 'sale_orders_timelog'));
        if ($this->utils->isEnabledFeature('responsible_gaming')) {
            $this->load->library(array('player_responsible_gaming_library'));
            //deposit limit hint
            if ($this->player_responsible_gaming_library->inDepositLimits($playerId, $depositAmount)) {
                
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return [
                    'status' => 'error',
                    'message' => lang('Deposit Limits Effect, cannot make deposit')
                ];
            }
        }

        if ($this->utils->isEnabledFeature('enable_deposit_upload_documents')) {
            $file1 = isset($_FILES['file1']) ? $_FILES['file1'] : null;
            $file2 = isset($_FILES['file2']) ? $_FILES['file2'] : null;
            $this->utils->debug_log('=========== upload attached document exist ? file1 [ '.json_encode($file1).' ] , file2 [ '.json_encode($file2).' ] ');
        }


        if($this->utils->isEnabledFeature('only_allow_one_pending_deposit')){

            $exists=$this->sale_order->existsUnfinishedManuallyDeposit($playerId);
            if($exists){
                $message = lang('Sorry, your last deposit request is not done, so you can not start new request');
                
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
            }
        }

		$error = null;
		$player_promo_id = null;
        $depositAccountNo = null;
		list($promo_cms_id, $promo_rules_id, $promorule) = $this->_process_promo_rules($playerId, $promo_cms_id, $depositAmount, $error, $sub_wallet_id);
        $this->utils->debug_log('--- postManualDeposit --- _process_promo_rules result', $promo_cms_id, $promo_rules_id, $promorule, $error, $sub_wallet_id);
        $promo_info['promo_rules_id']=$promo_rules_id;
		$promo_info['promo_cms_id']=$promo_cms_id;

        ############# OGP-25313 START REGISTER TO IOVATION #########
        $this->CI->load->library(['iovation_lib']);
        $isIovationEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;

        $ioBlackBox = $this->input->post('ioBlackBox');
        if($isIovationEnabled && empty($ioBlackBox)){
            
            $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
            return $this->returnJsonResult(['status' => 'error', 'msg' => lang('notify.127')]);
        }

        if($playerId && $isIovationEnabled && !empty($ioBlackBox) && !empty($promo_cms_id)){
            $this->load->model(['player_model']);

            $iovationparams = [
                'player_id'=>$playerId,
                'ip'=>$this->utils->getIP(),
                'blackbox'=>$ioBlackBox,
                'promo_cms_setting_id'=>$promo_cms_id,
            ];
            $iovationResponse = $this->CI->iovation_lib->registerPromotionToIovation($iovationparams, Iovation_lib::API_depositSelectPromotion);
            $this->utils->debug_log('Manual Deposit Iovation Promotion response', $iovationResponse);

            //start of promotion auto deny
            $isDeclineEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_auto_decline_promotion_if_denied');
            if($isDeclineEnabled && isset($iovationResponse['iovation_result']) && $iovationResponse['iovation_result']=='D'){
                $adminUserId = Transactions::ADMIN;
                $this->player_model->disablePromotionByPlayerId($playerId);

                //add player update history
                $changeMsg = lang('player.tp13');
                $this->player_model->savePlayerUpdateLog($playerId, $changeMsg, 'admin');
                $this->utils->debug_log('Manual Deposit savePlayerUpdateLog', $promo_rules_id, $playerId, $changeMsg);

                $tagName = 'Iovation Denied';
                $tagId = $this->player_model->getTagIdByTagName($tagName);
                if(empty($tagId)){
                    $tagId = $this->player_model->createNewTags($tagName,$adminUserId);
                }

                $this->player_model->addTagToPlayer($playerId, $tagId, $adminUserId);

                $message = lang('Promotion is disabled to this player.');
                $this->utils->debug_log('Manual Deposit isAllowedByClaimPeriod:', $message, 'tagId', $tagId);
                
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
            }//end of promotion auto deny
        }
        ############# END REGISTER TO IOVATION #########

		$payment_account = $this->payment_account->getPaymentAccountWithVIPRule($payment_account_id, $playerId);
		if (empty($payment_account)) {
            
            $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
            return $this->returnJsonResult(array('status' => 'error', 'msg' => sprintf(lang('gen.error.forbidden'), lang('pay.depmethod'))));
		}

        $payment_account_data = $this->_displayPaymentAccountData($payment_account, TRUE);
        try{
            #cryptocurrency deposit
            $banktype = $this->banktype->getBankTypeById($payment_account->bankTypeId);
            if($this->utils->isCryptoCurrency($banktype)){
                $this->load->library('session');
                $cryptoQty = $this->input->post('cryptoQty');
                $request_cryptocurrency_rate = $this->session->userdata('cryptocurrency_rate');
                $cryptocurrency = $this->utils->getCryptoCurrency($banktype);
                $cust_crypto_allow_compare_digital = $this->utils->getCustCryptoAllowCompareDigital($cryptocurrency);
                list($crypto, $rate) = $this->utils->convertCryptoCurrency($depositAmount, $cryptocurrency, $cryptocurrency, 'deposit');
                $this->utils->debug_log('---deposit session crypto rate and current crypto rate--- ', $request_cryptocurrency_rate, $rate);
                $this->session->unset_userdata('cryptocurrency_rate');
                $crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);

                if(!empty($rate) && !empty($request_cryptocurrency_rate)){
                    if(abs($rate - $request_cryptocurrency_rate) > $cust_crypto_allow_compare_digital){
                        $message = lang('The crypto rate is not in allow compare range');

                        $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                        return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                    }
                }

                if(!empty($request_cryptocurrency_rate) && !empty($cryptoQty)){
                    if($depositAmount != number_format(($request_cryptocurrency_rate * $cryptoQty)/$crypto_to_currecny_exchange_rate,2,'.','')){
                        $message = lang('The conversion result is not correct');

                        $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                        return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                    }
                }

                $custom_deposit_rate = $this->config->item('custom_deposit_rate') ? $this->config->item('custom_deposit_rate') : 1;

                $this->load->model(array('playerbankdetails'));
                $bankDetails = $this->playerbankdetails->getBankDetailsById($playerBankDetailsId);

                if($this->utils->getConfig('disabel_deposit_bank')){
                    $payment_type = 'withdrawal';
                }else{
                    $payment_type = 'deposit';
                }

                $cryptoPaymentAccount = $this->playerbankdetails->getCryptoAccountByPlayerId($playerId, $payment_type, $cryptocurrency);

                if(empty($bankDetails)){
                    $depositAccountNo = 'null';
                }else{
                    $depositAccountNo = $bankDetails['bankAccountNumber'];
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

            $minDeposit = $payment_account->vip_rule_min_deposit_trans;
            $maxDeposit = $payment_account->vip_rule_max_deposit_trans;
            $maxDepositDaily = 0;

            $deposit_order_args = [
                Sale_order::PAYMENT_KIND_DEPOSIT,
                $payment_account_id,
                $playerId,
                $depositAmount,
                $defaultCurrency,
                $player_promo_id,
                $dwIp,
                $geolocation['geoplugin_city'] . ',' . $geolocation['geoplugin_countryName'],
                $playerBankDetailsId,
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


            if ($depositAmount <= 0) {
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('notify.43')));
            } else if ($depositAmount < $minDeposit) {
                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('notify.43')));
            } else if ($maxDeposit > 0) {
                if ($depositAmount > $maxDeposit) {
                    $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                    return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('notify.46')));
                } elseif ($maxDepositDaily > 0) {
                    $playerTotalDailyDeposit = $this->transactions->sumDepositAmountToday($playerId);
                    if (($playerTotalDailyDeposit + $depositAmount) >= $maxDepositDaily) {
                        $message = lang('notify.46');
                        
                        $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                        return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                    }

                }else{
                    $msg = lang('Thank you for your deposit Please check back again later.');

                    $saleOrder = call_user_func_array([$this->sale_order, 'createDepositOrder'], $deposit_order_args);

                    if(isset($saleOrder['id'])){
                        $this->sale_order->addSaleOrderNotes($saleOrder['id'], $playerId, $deposit_notes, Sale_order::STATUS_PROCESSING, null, Sale_orders_timelog::PLAYER_USER, Sale_orders_notes::PLAYER_NOTES);
                    }

                    if (isset($crypto)) {
                        $show_player_msg = sprintf(lang('Please transfer to'), $payment_account->payment_account_number, $crypto, $this->utils->formatCurrencyNoSymwithDecimal($player_rate, 8));
                        //$this->utils->formatCurrencyNoSymwithDecimal($player_rate, 8)
                        $msg = $show_player_msg;
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

                    $message = array(
                        "status" => "success",
                        "order" => $saleOrder,
                        "payment_account_data" => $payment_account_data,
                        "msg" => $msg
                    );

                    if($this->utils->isEnabledFeature('enable_deposit_upload_documents')){
                        $response_1 = $this->upload_attached_document($file1,$saleOrder['id'],$playerId,true);
                        $response_2 = $this->upload_attached_document($file2,$saleOrder['id'],$playerId,true);
                        if(isset($response_1['status'])) {
                            if($response_1['status'] != 'success') {
                                $message = lang('File')."1: ".lang('Upload failed.');

                                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                                return $this->returnJsonResult(array('status' => 'error', 'msg' => $message, 'response_1' => $response_1));
                            }
                        }
                        if(isset($response_2['status'])) {
                            if($response_2['status'] != 'success') {
                                $message = lang('File')."2: ".lang('Upload failed.');

                                $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                                return $this->returnJsonResult(array('status' => 'error', 'msg' => $message, 'response_2' => $response_2));
                            }
                        }
                    }

                    $third_api_id = $this->utils->getConfig('third_party_api_id_when_manual_deposit');
                    if(!empty($third_api_id)){
                        $api = $this->utils->loadExternalSystemLibObject($third_api_id);
                        if (!empty($api)) {
                            $api->manualPaymentUrlForm($saleOrder, $playerId, $depositAmount, $deposit_time, $payment_account_id, $playerBankDetailsId);
                        }else{
                            $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                            return $this->utils->debug_log('--- not exist systemId or manualPaymentUrlForm function ---');
                        }
                    }

                    if(isset($saleOrder['id'])){
                        $affiliateOfPlayer = $this->player_model->getAffiliateOfPlayer($playerId);
                        $currency = $this->utils->getCurrentCurrency();

                        $postData = array(
                            'orderid'           => $this->utils->safeGetArray($saleOrder, 'id', ''),
                            'secure_id'         => $this->utils->safeGetArray($saleOrder, 'secure_id', ''),
                            'amount'            => $depositAmount,
                            "Type"              => "Deposit",
                            "Status"            => "Success",
                            "Currency"          => $currency['currency_code'],
                            "TransactionID"     => $this->utils->safeGetArray($saleOrder, 'secure_id', ''),
                            "Channel"           => $saleOrder['payment_account_name'],
                            "TimeTaken"         => "0",
                            "LastDepositAmount" => $depositAmount,
                            "SBE_affiliate"     => $affiliateOfPlayer,
                            "Date"              => $saleOrder['created_at'],
                        );

                        //posthog
                        if(!empty($promo_cms_id)){
                            $postData['selected_promo_id'] = $promo_cms_id;
                            $postData['selected_promo_name'] = $promo_data['promoName'];
                        }

                        $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT', $postData);
                    }

                    if($this->utils->getConfig('enable_fast_track_integration')) {
                        $this->load->library('fast_track');
                        $this->fast_track->requestDeposit($saleOrder);
                    }
                    $this->saveHttpRequest($playerId, Http_request::TYPE_DEPOSIT);
                    return $this->returnJsonResult($message);
                }
            }else{
                $saleOrder = call_user_func_array([$this->sale_order, 'createDepositOrder'], $deposit_order_args);

                if(isset($saleOrder['id'])){
                    $this->sale_order->addSaleOrderNotes($saleOrder['id'], $playerId, $deposit_notes, Sale_order::STATUS_PROCESSING, null, Sale_orders_timelog::PLAYER_USER, Sale_orders_notes::PLAYER_NOTES);
                }

                $message = array(
                    "status" => "success",
                    "order" => $saleOrder,
                    "payment_account_data" => $payment_account_data,
                    "msg" => lang('Thank you for your deposit Please check back again later.'),
                );

                if($this->utils->isEnabledFeature('enable_deposit_upload_documents')){
                    $response_1 = $this->upload_attached_document($file1,$saleOrder['id'],$playerId,true);
                    $response_2 = $this->upload_attached_document($file2,$saleOrder['id'],$playerId,true);
                    if(isset($response_1['status'])) {
                        if($response_1['status'] != 'success') {
                            $message = lang('File')."1: ".lang('Upload failed.');

                            $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                            return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                        }
                    }
                    if(isset($response_2['status'])) {
                        if($response_2['status'] != 'success') {
                            $message = lang('File')."2: ".lang('Upload failed.');

                            $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                            return $this->returnJsonResult(array('status' => 'error', 'msg' => $message));
                        }
                    }
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

                $third_api_id = $this->utils->getConfig('third_party_api_id_when_manual_deposit');
                if(!empty($third_api_id)){
                    $api = $this->utils->loadExternalSystemLibObject($third_api_id);
                    if (!empty($api)) {
                        $api->manualPaymentUrlForm($saleOrder, $playerId, $depositAmount, $deposit_time, $payment_account_id, $playerBankDetailsId);
                    }else{
                        $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
                        return $this->utils->debug_log('--- not exist systemId or manualPaymentUrlForm function ---');
                    }
                }

                if(isset($saleOrder['id'])){
                    $affiliateOfPlayer = $this->player_model->getAffiliateOfPlayer($playerId);
                    $currency = $this->utils->getCurrentCurrency();
                    
                    $postData = array(
                        'orderid'           => $this->utils->safeGetArray($saleOrder, 'id', ''),
                        'secure_id'         => $this->utils->safeGetArray($saleOrder, 'secure_id', ''),
                        'amount'            => $depositAmount,
                        "Type"              => "Deposit",
                        "Status"            => "Success",
                        "Currency"          => $currency['currency_code'],
                        "TransactionID"     => $this->utils->safeGetArray($saleOrder, 'secure_id', ''),
                        "Channel"           => $saleOrder['payment_account_name'],
                        "TimeTaken"         => "0",
                        "LastDepositAmount" => $depositAmount,
                        "SBE_affiliate"     => $affiliateOfPlayer,
                        "Date"              => $saleOrder['created_at'],
                    );

                    //posthog
                    if(!empty($promo_cms_id)){
                        $postData['selected_promo_id'] = $promo_cms_id;
                        $postData['selected_promo_name'] = $promo_data['promoName'];
                    }

                    $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT', $postData);
                }

                if($this->utils->getConfig('enable_fast_track_integration')) {
                    $this->load->library('fast_track');
                    $this->fast_track->requestDeposit($saleOrder);
                }
                $this->saveHttpRequest($playerId, Http_request::TYPE_DEPOSIT);
                return $this->returnJsonResult($message);
            }
        } catch (Exception $ex) {
            $this->utils->playerTrackingEvent($playerId, 'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED', $postData);
            return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('notify.39')));
        }
    }

    public function postCorrespondPaymentAccounts() {
        $this->load->model(array('payment_account', 'playerbankdetails'));
        // $this->utils->debug_log('====================postCorrespondPaymentAccounts input post', $this->input->post());

        //get correspond payemnt accounts by the same bankTypeId that player select (just for now, may changed the logic in the future.)
        $player_select_bank_data = $this->input->post();
        // $this->playerbankdetails->getBankDetailsById($player_select_bank_data['bankDetailsId']);

        $player_id = $this->authentication->getPlayerId();
        $payment_all_accounts = $this->payment_account->getAvailableDefaultCollectionAccount($player_id);
        // $this->utils->debug_log('====================postCorrespondPaymentAccounts payment_all_accounts', $payment_all_accounts);

        $payment_manual_accounts = [];

        if($payment_all_accounts[MANUAL_ONLINE_PAYMENT]['enabled']){
            foreach($payment_all_accounts[MANUAL_ONLINE_PAYMENT]['list'] as $payment_account){
                if($payment_account->payment_type_id == $player_select_bank_data['bankTypeId']) {
                    $payment_account->payment_account_title = sprintf('%s - %s', lang($payment_account->payment_type), $payment_account->payment_account_name);
                    $payment_manual_accounts[] = $payment_account;
                }
            }
        }

        if($payment_all_accounts[LOCAL_BANK_OFFLINE]['enabled']){
            foreach($payment_all_accounts[LOCAL_BANK_OFFLINE]['list'] as $payment_account){
                if($payment_account->payment_type_id == $player_select_bank_data['bankTypeId']) {
                    $payment_account->payment_account_title = sprintf('%s - %s', lang($payment_account->payment_type), $payment_account->payment_account_name);
                    $payment_manual_accounts[] = $payment_account;
                }
            }
        }
        $this->utils->debug_log('====================postCorrespondPaymentAccounts payment_manual_accounts', $payment_manual_accounts);

        return $this->returnJsonResult($payment_manual_accounts);
    }

    public function postUploadAttachedDocupent() {
        $this->utils->debug_log('===========postUploadAttachedDocupent', $this->input->post());
        try{
            $this->load->model(array('sale_order'));

            $player_id = $this->load->get_var('playerId');
            $secure_id = $this->input->post('secure_id');

            $file1 = isset($_FILES['file1']) ? $_FILES['file1'] : null;
            $file2 = isset($_FILES['file2']) ? $_FILES['file2'] : null;

            $sale_order = $this->sale_order->getSaleOrderBySecureId($secure_id);
            if(!empty($sale_order)) {
                if($this->utils->isEnabledFeature('enable_deposit_upload_documents')){
                    $return_status = 'error';
                    $return_msg = lang('collection.upload.msg.unsuccess');

                    if( (is_null($file1)) && (is_null($file2)) ) {
                        if($sale_order->payment_flag == LOCAL_BANK_OFFLINE) {
                            $return_msg = lang('Please upload at least one file when using ATM/Cashier payment account.');
                            $return_status = 'error';
                        }
                        else if($sale_order->payment_flag == MANUAL_ONLINE_PAYMENT) {
                            $return_msg = lang('You didn\'t upload any files.');
                            $return_status = 'success';
                        }
                    }

                    $response_1 = $this->upload_attached_document($file1, $sale_order->id, $player_id, true);
                    $response_2 = $this->upload_attached_document($file2, $sale_order->id, $player_id, true);

                    if(isset($response_1['status'])) {
                        if($response_1['status'] == 'success') {
                            $return_msg = lang('File')."1: ".lang('Successfully uploaded.');
                            $return_status = 'success';
                        }
                        else {
                            $return_msg = lang('File')."1: ".lang('Upload failed.');
                            $return_status = 'error';
                        }
                    }

                    if(isset($response_2['status'])) {
                        if($response_2['status'] == 'success') {
                            $return_msg .= lang('File')."2: ".lang('Successfully uploaded.');
                            $return_status = 'success';
                        }
                        else {
                            $return_msg = lang('File')."2: ".lang('Upload failed.');
                            $return_status = 'error';
                        }
                    }

                    return $this->returnJsonResult(array('status' => $return_status, 'msg' => $return_msg));
                }
                else {
                    return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('Upload failed caused by not enabling deposit upload documents.')));
                }
            }
            else {
                return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('Upload failed caused by cannot find this order.')));
            }

        } catch (Exception $ex) {
            return $this->returnJsonResult(array('status' => 'error', 'msg' => lang('An error occurred and the upload failed.')));
        }
    }

    public function upload_attached_document($file , $reference_id , $playerId, $return_origin_msg= false){
        if(!empty($file) && !empty($reference_id) && !empty($playerId)){
            $this->load->model(array('player_attached_proof_file_model'));

            $input = array(
                "player_id"       => $playerId,
                "tag"             => player_attached_proof_file_model::Deposit_Attached_Document,
                "sales_order_id"  => $reference_id,
            );

            $data = [
                'input' => $input,
                'image' => $file
            ];

            $response = $this->player_attached_proof_file_model->upload_deposit_receipt($data);

            if($return_origin_msg) return $response;
            return $this->returnJsonResult($response);
        }
    }

    // OGP-17216 Deprecate add_security_on_deposit_transaction feature in deposit page
    // public function postManualDepositBanks(){
    //     $withdrawal_password = $this->input->post('withdrawal_password');
    //     $playerId = $this->load->get_var('playerId');

    //     $checkWithdrawPassword = $this->player_model->validateWithdrawalPassword($playerId, $withdrawal_password);
    //     if(!$checkWithdrawPassword){
    //         return $this->returnJsonResult(array(
    //             'success' => 'error',
    //             'msg' => lang('Invalid Withdrawal Password')
    //         ));
    //     }

    //     $payment_all_accounts = $this->payment_account->getAvailableDefaultCollectionAccount($playerId);

    //     $payment_manual_accounts = ($payment_all_accounts[MANUAL_ONLINE_PAYMENT]['enabled']) ? $payment_all_accounts[MANUAL_ONLINE_PAYMENT]['list'] : [];
    //     if($payment_all_accounts[LOCAL_BANK_OFFLINE]['enabled']){
    //         foreach($payment_all_accounts[LOCAL_BANK_OFFLINE]['list'] as $payment_account){
    //             $payment_manual_accounts[] = $payment_account;
    //         }
    //     }

    //     $deposit_secure_verify_show_payment_account_counts = $this->config->item('deposit_secure_verify_show_payment_account_counts');
    //     $deposit_secure_verify_show_payment_account_counts = (FALSE === $deposit_secure_verify_show_payment_account_counts) ? PHP_INT_MAX : $deposit_secure_verify_show_payment_account_counts;

    //     $payment_accounts = [];
    //     $count = 0;
    //     foreach($payment_manual_accounts as $payment_account){
    //         if($count >= $deposit_secure_verify_show_payment_account_counts) break;

    //         $payment_accounts[] = $this->_displayPaymentAccountData($payment_account);

    //         $count++;
    //     }

    //     return $this->returnJsonResult(array('status' => 'success', 'payment_accounts' => $payment_accounts));
    // }
}
