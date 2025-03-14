<?php
require_once 'PlayerCenterBaseController.php';

/**
 * Provides withdrawal function in player center dashboard
 */
class Withdraw extends PlayerCenterBaseController {
	const WITHDRAWAL_ENABLED = 1;
	const WITHDRAWAL_DISABLED = 0;
	const NUM_CHAR_DISPLAY = 5;
	const BANK_TYPE_ARR = ['bank','alipay','wechat','customBank'];

	public function __construct(){
		parent::__construct();
		$this->load->helper('url');
		$this->preloadCashierVars();
	}

	public function index() {
		$this->load->model(['player_model']);

		$enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
		if( ! empty($enable_OGP19808) ){
			$playerId = $this->load->get_var('playerId');
			$result4fromLine = $this->player_model->check_playerDetail_from_line($playerId);
			if($result4fromLine['success'] === false ){
				if( $this->utils->is_mobile() ){
					$url = site_url( $this->utils->getPlayerProfileUrl() );
				}else{
					$url = site_url( $this->utils->getPlayerProfileSetupUrl() );
				}
				return redirect($url);
			}
		} // EOF if( ! empty($enable_OGP19808) ){...

		return $this->bank();
	}

	public function bank() {
		$this->load->vars('type', 'bank');
		$this->loadBanks('bank');

        $enabled_withdrawal_custom_page = $this->utils->getConfig('enabled_withdrawal_custom_page');
        $custom_lang = $this->utils->getConfig('custom_lang');
		if (!empty($enabled_withdrawal_custom_page[$custom_lang]['custom_withdrawal_bankcode'])) {
            $this->load->vars('custom_type', 'customBank');
            $this->loadCustomBanks('customBank');
		}

		return $this->display();
	}

	public function customBank() {
		$this->load->vars('custom_type', 'customBank');
		$this->loadCustomBanks('customBank');
		if (!empty($this->utils->getConfig('enabled_withdrawal_page'))) {
			$this->load->vars('type', 'bank');
			$this->loadBanks('bank');
		}
		return $this->display();
	}

	public function wechat() {
		$this->load->vars('type', 'wechat');
		$this->loadBanks('wechat');
		return $this->display();
	}

	public function alipay() {
		$this->load->vars('type', 'alipay');
		$this->loadBanks('alipay');
		return $this->display();
	}

	private function loadBanks($type) {
		$this->load->model(['banktype', 'playerbankdetails']);
		$playerId = $this->load->get_var('playerId');
		$bankList = $this->utils->getAllBankTypes();

		$this->load->vars('bankList', $bankList);
		$this->load->vars(['isAlipayEnabled' => false]);
		$this->load->vars(['isWeChatEnabled' => false]);
		$bank_type_alipay = $this->playerbankdetails->BANK_TYPE_ALIPAY;
		$bank_type_wechat = $this->playerbankdetails->BANK_TYPE_WECHAT;

		# Record down whether alipay or wechat is enabled for withdrawal
		foreach($bankList as $bank) {
			if($bank['bankTypeId'] == $bank_type_alipay) {
				$this->load->vars(['isAlipayEnabled' => $bank['enabled_withdrawal']]);
			} elseif ($bank['bankTypeId'] == $bank_type_wechat) {
				$this->load->vars(['isWeChatEnabled' => $bank['enabled_withdrawal']]);
			}
		}

		# Player's saved bank accounts
		switch ($type) {
			case 'bank':
				$playerBankDetails = $this->playerbankdetails->getWithdrawBankOnlyDetail($playerId);
				// OGP-2513: generate bank icon url for each bank entry
				foreach ($playerBankDetails as $key => $bank) {
					$playerBankDetails[$key]['bank_icon_url'] = '';
					$playerBankDetails[$key]['is_crypto'] = 0;
					if (!empty($bank['bankIcon'])) {
						$playerBankDetails[$key]['bank_icon_url'] = Banktype::getBankIcon($bank['bankIcon']);
					}
					$banktype = $this->banktype->getBankTypeById($playerBankDetails[$key]['bankTypeId']);
					if($this->utils->isCryptoCurrency($banktype)) {
						$playerBankDetails[$key]['is_crypto'] = 1;
					}
				}
				break;
			case 'wechat':
				$playerBankDetails = $this->playerbankdetails->getWithdrawWeChatAccountDetail($playerId);
				foreach ($playerBankDetails as $key => $bank) {
					$playerBankDetails[$key]['is_crypto'] = 0;
				}
				break;
			case 'alipay':
				$playerBankDetails = $this->playerbankdetails->getWithdrawAlipayAccountDetail($playerId);
				foreach ($playerBankDetails as $key => $bank) {
					$playerBankDetails[$key]['is_crypto'] = 0;
				}
				break;
			default:
				$playerBankDetails = array();
				break;
		}

		# Fill accounts with displayName, modifying the array in foreach
		foreach($playerBankDetails as $bankIndex => $bank) {
            $playerBankDetails[$bankIndex]['bankAccountNumber'] = Playerbankdetails::getDisplayAccNum($bank['bankAccountNumber']);
            $playerBankDetails[$bankIndex]['displayName'] = lang($bank['bankName']).' ('.$playerBankDetails[$bankIndex]['bankAccountNumber'].')';
		}

		# Player's default bank account / last used bank account
		$lastUsedBankDetailsId = $this->session->flashdata('bankDetailsId');
		foreach($playerBankDetails as $playerBankDetail) {
			if(empty($lastUsedBankDetailsId) && $playerBankDetail['isDefault'] == 1
				|| $lastUsedBankDetailsId == $playerBankDetail['playerBankDetailsId']) {
				$playerDefaultBankDetail = $playerBankDetail;
				break;
			}
		}
		if(empty($playerDefaultBankDetail) && !empty($playerBankDetails)) {
			# No default bank set, use the first stored bank
			$playerDefaultBankDetail = $playerBankDetails[0];
		}

		$this->load->vars('playerBankDetails', $playerBankDetails);
		if(!empty($playerDefaultBankDetail)) {
			$this->load->vars('playerDefaultBankDetail', $playerDefaultBankDetail);
		}
	}

	private function loadCustomBanks($type) {
		$this->load->model(['banktype', 'playerbankdetails']);
		$playerId = $this->load->get_var('playerId');
		$bankList = $this->utils->getAllBankTypes();

		$this->load->vars('bankList', $bankList);
		# Player's saved bank accounts
		$playerBankDetails = array();
		$playerDefaultBankDetail = array();

		switch ($type) {
			case 'customBank':
				$playerBankDetails = $this->playerbankdetails->getWithdrawBankOnlyDetail($playerId);
				$customBankList = $this->utils->getConfig('enabled_withdrawal_custom_page')[$this->utils->getConfig('custom_lang')]['custom_withdrawal_bankcode'];

				foreach ($playerBankDetails as $key => $bank) {
					if (!in_array(strtoupper($bank['bank_code']), $customBankList)) {
						unset($playerBankDetails[$key]);
						continue;
					}

					$playerBankDetails[$key]['bank_icon_url'] = '';
					$playerBankDetails[$key]['is_crypto'] = 0;
					if (!empty($bank['bankIcon'])) {
						$playerBankDetails[$key]['bank_icon_url'] = Banktype::getBankIcon($bank['bankIcon']);
					}
					$banktype = $this->banktype->getBankTypeById($playerBankDetails[$key]['bankTypeId']);
					if($this->utils->isCryptoCurrency($banktype)) {
						$playerBankDetails[$key]['is_crypto'] = 1;
					}
				}
				break;
			default:
				break;
		}

		$playerBankDetails = array_values($playerBankDetails);
		$this->utils->debug_log(__METHOD__, 'playerBankDetails', $playerBankDetails);

		# Fill accounts with displayName, modifying the array in foreach
		foreach($playerBankDetails as $bankIndex => $bank) {
            $playerBankDetails[$bankIndex]['bankAccountNumber'] = Playerbankdetails::getDisplayAccNum($bank['bankAccountNumber']);
            $playerBankDetails[$bankIndex]['displayName'] = lang($bank['bankName']);
            // $playerBankDetails[$bankIndex]['displayName'] = lang($bank['bankName']).' ('.$playerBankDetails[$bankIndex]['bankAccountNumber'].')';
		}

		# Player's default bank account / last used bank account
		$lastUsedBankDetailsId = $this->session->flashdata('customBankDetailsId');
		foreach($playerBankDetails as $playerBankDetail) {
			if(empty($lastUsedBankDetailsId) && $playerBankDetail['isDefault'] == 1
				|| $lastUsedBankDetailsId == $playerBankDetail['playerBankDetailsId']) {
				$playerDefaultBankDetail = $playerBankDetail;
				break;
			}
		}
		if(empty($playerDefaultBankDetail) && !empty($playerBankDetails)) {
			# No default bank set, use the first stored bank
			$playerDefaultBankDetail = $playerBankDetails[0];
		}

		$this->utils->debug_log(__METHOD__, 'playerBankDetails', $playerBankDetails);
		$this->utils->debug_log(__METHOD__, 'playerDefaultBankDetail', $playerDefaultBankDetail);

		$this->load->vars('playerCustoBankDetails', $playerBankDetails);
		if(!empty($playerDefaultBankDetail)) {
			$this->load->vars('playerDefaultCustoBankDetail', $playerDefaultBankDetail);
		}
	}

	private function getDisplayAccNum($accNum) {
		if(strlen($accNum) <= self::NUM_CHAR_DISPLAY){
			return $accNum;
		}
		return '*'.substr($accNum, -self::NUM_CHAR_DISPLAY);
	}

	private function display(){

		$this->load->model(['system_feature','risk_score_model', 'financial_account_setting', 'playerbankdetails', 'group_level', 'wallet_model', 'player_preference']);

		$result = $this->session->flashdata('result');

		$playerId = $this->load->get_var('playerId');
		$player = $this->load->get_var('player');

		if(!$this->checkAgencyCreditMode($playerId)){
            return;
        }

		#OGP-19051 show withdraw fee
		$data['playerId'] = $playerId;
		$data['levelId'] = $player['levelId'];

		# Withdraw settings
		$data['withdrawSetting'] = $this->utils->getWithdrawMinMax($playerId);

		# Player's name, needed when creating new deposit account
		$data['realname'] = Player::getPlayerFullName($player['firstName'], $player['lastName'], $player['language']);

		$data['has_withdraw_password'] = (!empty($player['withdraw_password']));

		# Message about the last data submission
		$data['result'] = $result;

		if($player['enabled_withdrawal'] == self::WITHDRAWAL_DISABLED) {
			$preference_row = $this->player_preference->getPlayerDisabledWithdrawalUntilByPlayerId($playerId);
			if( empty($preference_row['disabled_withdrawal_until']) ){
				$withdrawal_disabled_message_lang = lang('withdrawal_disabled_message');
			}else{
				$disabled_withdrawal_until = $preference_row['disabled_withdrawal_until'];
				if( $this->language_function->getCurrentLanguage() == Language_function::INT_LANG_PORTUGUESE ){
					$formatedDatetimeForDisplay = $this->utils->formatDatetimeForDisplay(new DateTime($disabled_withdrawal_until), 'd/m/Y H:i:s');
				}else{
					$formatedDatetimeForDisplay = $this->utils->formatDatetimeForDisplay(new DateTime($disabled_withdrawal_until));
				}
				$disabled_withdrawal_until_message = lang('disabled_withdrawal_until_message');
				$withdrawal_disabled_message_lang = sprintf($disabled_withdrawal_until_message, $formatedDatetimeForDisplay);
			}
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $withdrawal_disabled_message_lang);
			return redirect('/');
		}

        $data['player_preference']  = $this->player_preference->getPlayerPreferenceDetailsByPlayerId($playerId);

		if ($this->utils->isEnabledFeature("show_allowed_withdrawal_status") && $this->utils->isEnabledFeature("show_risk_score") && $this->utils->isEnabledFeature("show_kyc_status")) {
			$data['allowed_withdrawal_status_kyc_risk_score'] = $this->risk_score_model->generate_allowed_withdrawal_status($playerId);
		} else {
			$data['allowed_withdrawal_status_kyc_risk_score'] = true;
		}

		$data['current_controller'] = 'player_center2';

		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);

		$withdrawal_crypto_currency = $this->config->item('enable_withdrawal_crypto_currency');
		$data['withdrawal_crypto_currency'] = false;
		$data['exist_crypto_account'] = false;
		$data['cryptocurrencies'] = false;
        if($this->config->item('cryptocurrencies')){
            $cryptocurrencies = $this->config->item('cryptocurrencies');
            $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
            $data['defaultCurrency'] = $defaultCurrency;
            foreach($cryptocurrencies as $cryptocurrency){
                list($crypto, $rate) = $this->utils->convertCryptoCurrency(1, $cryptocurrency, $cryptocurrency, 'withdrawal');
                $custom_withdrawal_rate = $this->config->item('custom_withdrawal_rate') ? $this->config->item('custom_withdrawal_rate') : 1;
                // $player_rate = number_format($rate * $custom_withdrawal_rate, 4, '.', '');
                $custCryptoUpdateTiming = $this->utils->getCustCryptoUpdateTiming($cryptocurrency);
                $custCryptoInputDecimalPlaceSetting = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency);
                $custCryptoInputDecimalReciprocal = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
                $label_lang = sprintf(lang('Converter Crypto Withdrawal Amount'), lang("$cryptocurrency-Crypto"));
                $cryptocurrency_lang = lang("$cryptocurrency-Crypto");
                $data_step_error_lang = sprintf(lang('notify.124'), $custCryptoInputDecimalPlaceSetting);
                $converter_crypto_withdrawal_amount_lang = sprintf(lang('Converter Crypto Withdrawal Amount'), $cryptocurrency_lang);
                $crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
                $data['cryptocurrencies'][$cryptocurrency] = array(
                	'rate' => $rate ,
                	'custCryptoUpdateTiming' => $custCryptoUpdateTiming ,
                	'custCryptoInputDecimalPlaceSetting' => $custCryptoInputDecimalPlaceSetting,
                	'custCryptoInputDecimalReciprocal' => $custCryptoInputDecimalReciprocal,
                	'label_lang' => $label_lang,
                	'cryptocurrency' => $cryptocurrency_lang,
                	'data_step_error_lang' => $data_step_error_lang,
                	'converter_crypto_withdrawal_amount_lang'=> $converter_crypto_withdrawal_amount_lang,
                	'defaultCurrency'=> lang("$defaultCurrency-Yuan"),
                	'cryptoToCurrecnyExchangeRate'=> $crypto_to_currecny_exchange_rate
                );
                $cryptocurrencies_rates[$cryptocurrency] = $rate;
                $data['is_cryptocurrency'] = TRUE;
                // $data['exist_crypto_account'] = !empty($this->playerbankdetails->getCryptoAccountByPlayerId($playerId, 'withdrawal',$cryptocurrency)) ? true :false ;
                $player_crypto_account = $this->playerbankdetails->getCryptoAccountByPlayerId($playerId, 'withdrawal', $cryptocurrency);
                $data['player_crypto_account'] = $player_crypto_account;
            }
            $this->load->library('session');
            $this->session->set_userdata('cryptocurrencies_rates', json_encode($cryptocurrencies_rates));
            $this->utils->debug_log('============crypto withdrawal data', $data['cryptocurrencies']);
	    }elseif($withdrawal_crypto_currency['enabled']){
			#OGP-20534(OGP-20364)
			$data['withdrawal_crypto_currency'] = $withdrawal_crypto_currency;
	    }

		$enabled_change_withdrawal_password = $this->operatorglobalsettings->getSettingJson('enabled_change_withdrawal_password');
		$enabled_change_withdrawal_password = (empty($enabled_change_withdrawal_password)) ? ['disable'] : $enabled_change_withdrawal_password;

	    $data['enabled_withdrawal_password'] = in_array('enable', $enabled_change_withdrawal_password);
        $data['showMaxWithdrawalPerTransaction'] = 1;
        $data['showDailyMaxWithdrawalAmount'] = 1;
        $withdrawal_page_setting = $this->operatorglobalsettings->getPlayerCenterWithdrawalPageSetting();
        if(isset($withdrawal_page_setting)){
            $data['showMaxWithdrawalPerTransaction'] = $withdrawal_page_setting['showMaxWithdrawalPerTransaction'];
            $data['showDailyMaxWithdrawalAmount'] = $withdrawal_page_setting['showDailyMaxWithdrawalAmount'];
        }

	    $data['withdraw_amount_step_limit'] = $this->CI->config->item('withdraw_amount_step_limit');

        #OGP-22233
        $withdrawal_preset_setting = $this->operatorglobalsettings->getOperatorGlobalSetting('withdrawal_preset_amount');
        $data['withdrawal_preset_amount'] = $withdrawal_preset_setting[0]['value'];
        $data['main_wallet_balance'] = $this->player_model->getMainWalletBalance($playerId);

        #OGP-23415
        $enabled_withdrawal_fee_based_on_vip_level = $this->config->item('calculate_withdrawal_fee_based_on_vip_level');
        $data['withdrawal_fee_percentage'] = false;

		if ($enabled_withdrawal_fee_based_on_vip_level) {
			$first_day_of_this_month = date('Y-m-d H:i:s', strtotime('midnight first day of this month'));
			$today_date = $this->utils->getNowForMysql();
			$max_monthly_withdrawal = $data['withdrawSetting']['max_monthly_withdrawal'];

			$upg_res = $this->group_level->queryLastGradeRecordRowBy($playerId, $first_day_of_this_month, $today_date, null, 'request_time');
			$this->utils->debug_log(__METHOD__, "upg_res",$upg_res);

			if (!empty($upg_res)) {
				$accMonWithAmt = $this->wallet_model->sumWithdrawAmount($playerId, $upg_res['request_time'], $today_date, 0);
			}else{
				$accMonWithAmt = $this->wallet_model->sumWithdrawAmount($playerId, $first_day_of_this_month, $today_date, 0);
			}

			$freeFeeAmt = $max_monthly_withdrawal-$accMonWithAmt;

			$data['accumulatedMonthlyWithdrawalAmount'] = $freeFeeAmt > 0  ? $freeFeeAmt : 0;
			$data['withdrawal_fee_percentage'] = $this->get_withdrawal_fee_percentage($player['levelId']);
		}

		$data['withdrawal_start_time'] = false;
		$data['withdrawal_end_time'] = false;
		$enable_withdrawal_period = $this->utils->getConfig('enable_withdrawal_period');
		if (!empty($enable_withdrawal_period)) {
			$data['withdrawal_start_time'] = $enable_withdrawal_period['start_at'];
			$data['withdrawal_end_time'] = $enable_withdrawal_period['end_at'];
		}

		//items sequence
		$required_items = [
			'RECEIVIING_ACCOUNT',
			'WITHDRAWAL_AMOUNT',
			'WITHDRAW_VERIFICATION',
			'SMS_VERIFICATION'
		];
		$data['display_items'] = [];
		$custom_display_order = ($this->utils->is_mobile()) ? $this->CI->config->item('custom_player_center_withdrawal_display_items_sequence_for_mobile') : $this->CI->config->item('custom_player_center_withdrawal_display_items_sequence_for_desktop');
		$custom_display_order = is_array($custom_display_order) ? $custom_display_order : [];
		$data['display_items'] = array_values(array_unique(array_merge($custom_display_order, $required_items)));

		// display the player balance
		$data['show_player_balance'] = ($this->CI->config->item('display_player_balance_in_mobile_withdrawal_page') && $this->utils->is_mobile()) ? true : false;

		$data['player_verified_phone'] = $this->player_model->isVerifiedPhone($playerId);
		$data['player_verified_email'] = $this->player_model->isVerifiedEmail($playerId);
		$data['player_filled_birthday'] = $this->player_model->isFilledBirthdate($playerId);

        $data['show_wc_detail'] = $this->utils->getConfig('show_wc_detail_in_cashier_withdrawal');
        if($data['show_wc_detail']){
            $this->load->model(['withdraw_condition']);
            $unfinished = $this->withdraw_condition->existUnfinishWithdrawConditions($playerId);
            $data['wc_unfinished'] = $unfinished;
        }

		if ($this->utils->getConfig('enable_sms_verify_in_withdraw')) {
			$player_contact_info = $this->player->getPlayerContactInfo($playerId);
			$data['player_contact_info'] = $player_contact_info;
		}

		# Templates
		$this->loadTemplate();

		# Custom
		$this->template->add_js('/common/js/player_center/player-cashier.js');
		$this->template->add_js('/common/js/player_center/withdraw.js');
		$this->template->add_js('/common/js/plugins/province_city_select.js');
		$this->template->add_js('/resources/js/validator.js');
		$this->template->add_js('/resources/third_party/clipboard/clipboard.min.js');

		# Template-related variables
		$data['sub_nav_active'] = 'withdraw';

		# Render
        $this->template->append_function_title(lang('Withdrawal'));
		$this->template->write_view('main_content', $this->templateName . '/cashier/withdraw/withdraw', $data);
		$this->template->render();
	}

	//sum withdraw_data (totalRequiredBet / totalPlayerBet)
    private function multi_array_sum($arr,$key) {
		if ($arr) {
			$sum_no = 0;
			foreach($arr as $v){
			   $sum_no +=  $v[$key];
			 }
			return $sum_no;
		} else {
			return 0;
		}
	}

	public function get_withdrawal_fee_percentage($levelId){
		$fee_setting = $this->config->item('calculate_withdrawal_fee_based_on_vip_level');
		$percentage = '';
		foreach ($fee_setting as $level => $per) {
			if ($level == $levelId) {
				$percentage = $per . '%';
				return $percentage;
			}
		}

		return $percentage;
	}

	# Withdrawal form submits to this action
	public function verify() {
		$this->load->model(['playerbankdetails','risk_score_model', 'banktype', 'player_promo', 'transactions', 'users', 'wallet_model', 'sms_verification']);

		$playerId = $this->load->get_var('playerId');
		$player = $this->load->get_var('player');
		$this->utils->debug_log('verify playerId', $playerId, 'player', $player, 'input post', $this->input->post());
		$type = $this->input->post('type');

		if(!$this->checkAgencyCreditMode($playerId)){
            return;
        }

		if (!in_array($type, self::BANK_TYPE_ARR)) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('withdrawal_bank_type_not_exist')
			));

			return redirect('player_center2/withdraw');
		}

		$redirectPage = 'player_center2/withdraw'.'/'.$type;

		if ($player['enabled_withdrawal'] == self::WITHDRAWAL_DISABLED) {
			$this->utils->debug_log("Withdrawal attempted when withdrawal is disabled.");
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('withdrawal_disabled_message')
			));
			return redirect($redirectPage);
		}

		if ($this->utils->isEnabledFeature("show_allowed_withdrawal_status") && $this->utils->isEnabledFeature("show_risk_score") && $this->utils->isEnabledFeature("show_kyc_status")) {
			if(!$this->risk_score_model->generate_allowed_withdrawal_status($playerId)){
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('not_allowed_kyc_risk_score_message')
				));
				return redirect($redirectPage);
			}
		}

		if(!$this->verifyAndResetDoubleSubmit($playerId)){

			$message = lang('Please refresh and try, and donot allow double submit');
			if( $this->input->is_ajax_request() ){
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => $message
			));
			redirect($redirectPage);
			return;

		}

		if($this->utils->getConfig('check_sub_wallect_balance_in_withdrawal')){
			$balanceDetails = $this->wallet_model->getBalanceDetails($playerId);
			if(is_array($balanceDetails) && !empty($balanceDetails)){
				foreach ($balanceDetails['sub_wallet'] as $subWallet) {
					if($subWallet['totalBalanceAmount'] < 0){
						$message = sprintf(lang('SubWallet with negative balance'),$subWallet['game'],$subWallet['totalBalanceAmount']);
						$this->session->set_flashdata('result', array(
							'success' => false,
							'message' => $message
						));
						redirect($redirectPage);
						return;
					}
				}
			}else{
				$message = lang('check subwallet balance failed');
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => $message
				));
				redirect($redirectPage);
				return;
			}
		}

		$bankDetailsId = $this->input->post('bankDetailsId');

		if(empty($bankDetailsId)) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_bank_details_empty'));
			$this->utils->debug_log("=====================bankDetailsId", $bankDetailsId);
			redirect($redirectPage);
		}else {
			# Record down which id is used, display back the same id
			$this->session->set_flashdata('bankDetailsId', $bankDetailsId);
		}

		# SMS and Password verification is not yet present in new player center. Verify withdraw password only
		$withdrawVerificationMethod = $this->utils->getConfig('withdraw_verification');
		if($withdrawVerificationMethod == 'withdrawal_password'){
			$withdrawal_password = $this->input->post('withdrawal_password');
			$checkWithdrawPassword=$this->player_model->validateWithdrawalPassword($playerId, $withdrawal_password);
			if(!$checkWithdrawPassword){
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Invalid Withdrawal Password')
				));
				redirect($redirectPage);
			}
		}

		#sms verify in withdraw page
		if ($this->utils->getConfig('enable_sms_verify_in_withdraw')) {
			$contact_number = $this->player_model->getPlayerContactNumber($playerId);
			$sms_verification_code = $this->input->post('sms_verification_code');

			$this->utils->debug_log("enable_sms_verify_in_withdraw", $contact_number, $sms_verification_code);

			$verify_result = [];
			$usage = null;
			if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
				$usage = 'sms_api_withdrawal_setting';
			}

			$res = $this->update_sms_verification($contact_number,$sms_verification_code, $usage, $verify_result);
			$this->utils->debug_log("enable_sms_verify_in_withdraw res", $res);

			if(!$res['success']){
				$session_id = $this->session->userdata('session_id');
				$smsValidateResult = $this->sms_verification->validateSmsVerifiedStatus($playerId, $session_id, $contact_number, $sms_verification_code, $usage);

				$this->utils->debug_log('------------validateSmsVerifiedStatus smsValidateResult',$smsValidateResult);
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => $res['message']
				));
				redirect($redirectPage);
			}
		}

		# Validate withdraw limits
		$amount = $this->input->post('amount');
		$this->utils->debug_log("Withdrawal submitted by player [$playerId], amount [$amount], bankDetailsId [$bankDetailsId]");

		if($this->CI->config->item('disable_withdraw_amount_is_decimal')){
			if (is_numeric( $amount ) && floor( $amount ) != $amount) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.118')
				));
				redirect($redirectPage);
			}
		}
		// Verify if the submittng bankDetailsId belong to thie player
		$check_if_bank_detail_belnog_to_player = false;
		$check_bank_detail_enabled_withdrawal_to_player = false;
		$available_banks = $this->playerbankdetails->getAvailableWithdrawBankDetail($playerId);
		$this->utils->debug_log("=====================available_banks", $available_banks);
		if($available_banks == NULL) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_bank_details_null'));
			redirect($redirectPage);
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
			$this->utils->debug_log("=====================Verify player bank details error. The bankDetailsId: [$bankDetailsId] is not belong to the playerId: [$playerId].");
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_bank_details_binding_error'));
			redirect($redirectPage);
		}

		if(!$check_bank_detail_enabled_withdrawal_to_player) {
			$this->utils->debug_log("=====================Financial Institution bank account is disable");
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_Financial_account_enable'));
			redirect($redirectPage);
		}

		## Check whether withdrawal satisfies withdrawal rule for this player
        $playerWithdrawalRule = $this->utils->getWithdrawMinMax($playerId);
		list($withdrawalProcessingCount, $processingAmount) = $this->wallet_model->countTodayProcessingWithdraw($playerId);
		list($withdrawalPaidCount, $paidAmount) = $this->transactions->count_today_withdraw($playerId);
		$amount_used = $processingAmount + $paidAmount;
		$time_used = $withdrawalProcessingCount + $withdrawalPaidCount;

		if (!$playerWithdrawalRule || is_null($playerWithdrawalRule['max_withdraw_per_transaction'])) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('Undefined withdraw rule')
			));
			redirect($redirectPage);
		}
		else if ($amount > $playerWithdrawalRule['max_withdraw_per_transaction']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('Max Withdrawal Per Transaction is').$playerWithdrawalRule['max_withdraw_per_transaction']
			));
			redirect($redirectPage);
		}
		if ($amount + $amount_used > $playerWithdrawalRule['daily_max_withdraw_amount']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('notify.56')
			));
			redirect($redirectPage);
		}
		if ($time_used >= $playerWithdrawalRule['withdraw_times_limit']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('notify.106')
			));
			redirect($redirectPage);
		}

		if ($amount < $playerWithdrawalRule['min_withdraw_per_transaction']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('notify.102') . $playerWithdrawalRule['min_withdraw_per_transaction']
			));
			redirect($redirectPage);
		}

		#check  withdrawal conditions
		if ($this->utils->isEnabledFeature('check_withdrawal_conditions')) {
            if(FALSE !== $un_finished = $this->withdraw_condition->getPlayerUnfinishedWithdrawCondition($playerId)){
                //un_finished_withdrawal
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.withdrawal.condition')
				));
                redirect($redirectPage);
            }
		}

		#check  withdrawal conditions -- for each
		if ($this->utils->isEnabledFeature('check_withdrawal_conditions_foreach')) {
			if( FALSE !== $withdraw_data = $this->withdraw_condition->getPlayerUnfinishedWithdrawConditionForeach($playerId)){
                //un_finished
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.withdrawal.condition')
				));
                redirect($redirectPage);
            }
		}

		##check deposit conditions in withdrawal conditions -- for each
        if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')){
            if(FALSE !== $un_finished_deposit = $this->withdraw_condition->getPlayerUnfinishedDepositConditionForeach($playerId)){
                //un_finished_deposit
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.withdrawal.condition')
				));
                redirect($redirectPage);
            }
        }

		## Check whether multiple pending withdraws are allowed
		$this->load->model('group_level');
		if ($this->group_level->isOneWithdrawOnly($playerId)) {
			$this->load->model('player_model');
			if ($this->player_model->countWithdrawByStatusList($playerId) >= 1) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Sorry, your last withdraw request is not done, so you can\'t start new request')
				));
				redirect($redirectPage);
			}
		}

		#OGP-23514 Check Daily open withdrawal time
		$enable_withdrawal_period = $this->utils->getConfig('enable_withdrawal_period');
		if (!empty($enable_withdrawal_period['start_at']) && !empty($enable_withdrawal_period['end_at'])) {
			$currentDateTime = new DateTime();
			$currentDate = $this->utils->formatDateForMysql($currentDateTime);
			$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);
			$withdrawal_start_time = $currentDate . ' ' . $enable_withdrawal_period['start_at'] . ':00:00';
			$withdrawal_end_time = $currentDate . ' ' . $enable_withdrawal_period['end_at'] . ':00:00';
			$this->utils->debug_log(__METHOD__,'currentDateTime',$currentDateTime,$currentDate,$currentServertime,$withdrawal_start_time,$withdrawal_end_time);

			if (strtotime($currentServertime) < strtotime($withdrawal_start_time) || strtotime($currentServertime) > strtotime($withdrawal_end_time)) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Your application is not within the withdrawal period')
				));
				redirect($redirectPage);
			}
		}

		# All checks done, we can now proceed to submit withdrawal request
		## Save bank info if it's new (no bankDetailsId given)
		if(!$bankDetailsId) {
			$data = array(
				'playerId' => $playerId,
				'bankTypeId' => $this->input->post('bankTypeId'),
				'bankAccountNumber' => $this->input->post('bankAccNum'),
				'bankAccountFullName' => $this->input->post('bankAccName'),
				'bankAddress' => $this->input->post('bankAddress'),
				'province' => $this->input->post('province'),
				'city' => $this->input->post('city'),
				'branch' => $this->input->post('branch'),
				'isRemember' => Playerbankdetails::REMEMBERED,
				'dwBank' => Playerbankdetails::WITHDRAWAL_BANK,
            	'verified' => '1',
				'status' => Playerbankdetails::STATUS_ACTIVE,
				'phone' => $this->input->post('phone'),
			);

	        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_default_unverified')){
	            $data['verified'] = '0';
	        }

			if(empty($data['bankTypeId'])) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Sorry, please specify receiving account information')
				));
				redirect($redirectPage);
			}
			$this->load->library('player_functions');
			$bankDetailsId = $this->player_functions->addBankDetailsByWithdrawal($data);
			# Again, record down which id is used, display back the same id
			$this->session->set_flashdata('bankDetailsId', $bankDetailsId);
		}

		$playerAccount = $this->player_model->getPlayerAccountByPlayerIdOnly($playerId);
		$dwIp = $this->utils->getIP();
		$geolocation = $this->utils->getGeoplugin($dwIp);

		# Get bank details
		$playerBankDetails = $this->playerbankdetails->getBankList(array('playerBankDetailsId' => $bankDetailsId))[0];

        $banktype = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId']);
		$bankName = $banktype->bankName;

		$this->load->library('payment_library');
		$withdrawFeeAmount = 0;
		$calculationFormula = '';
		#if enable config get withdrawFee from player
		if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
			list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player['levelId'], $amount);

			$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);
			$this->utils->debug_log('enable_withdrawl_fee_from_player' , $mainAmount, $amount, $withdrawFeeAmount, $calculationFormula);
			if ($amount + $withdrawFeeAmount > $mainAmount) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance')
				));
				redirect($redirectPage);
			}
		}

		$withdrawBankFeeAmount = 0;
		$calculationFormulaBank = '';
		if ($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)) {
			list($withdrawBankFeeAmount,$calculationFormulaBank) = $this->payment_library->calculationWithdrawalBankFee($playerId, $banktype->bank_code, $amount);

			$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);

			if ($withdrawFeeAmount > 0) {
				$checkFeeAmt = $amount + $withdrawBankFeeAmount + $withdrawFeeAmount;
			}else{
				$checkFeeAmt = $amount + $withdrawBankFeeAmount;
			}

			$this->utils->debug_log('enable_withdrawl_bank_fee' , $mainAmount, $amount, $withdrawBankFeeAmount, $calculationFormulaBank);
			if ($checkFeeAmt > $mainAmount) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance')
				));
				redirect($redirectPage);
			}
		}

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
			'withdrawal_bank_fee' => $withdrawBankFeeAmount,
		);

		# OGP-3531
		if($this->utils->isEnabledFeature('enable_withdrawal_pending_review')){
			if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId)){
				$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
			}

			# OGP-3996 // add withdrawal_pending_review to risk score chart and set 1 = true unset if false in System settings->Risk score setting
			if($this->utils->isEnabledFeature('enable_withdrawal_pending_review_in_risk_score') && $this->utils->isEnabledFeature('show_risk_score')) {
				$risk_score_levels = $this->risk_score_model->getRiskScoreInfo(risk_score_model::RC);
				$this->utils->debug_log("risk_score_levels enable_withdrawal_pending_review_in_risk_score", "enabled");
				if(!empty($risk_score_levels)){
					$this->utils->debug_log("risk_score_levels not empty", true);
					if(isset($risk_score_levels['rules'])){
						$this->utils->debug_log("risk_score_levels rules set", true);
						$rules = json_decode($risk_score_levels['rules'],true);
						if(!empty($rules)) {
							$this->utils->debug_log("risk_score_levels rules not empty", true);
							foreach ($rules as $key => $value) {
								if(isset($value['withdrawal_pending_review'])){
									$this->utils->debug_log("risk_score_levels withdrawal_pending_review set", true);
									if($value['withdrawal_pending_review']){
										$this->utils->debug_log("risk_score_levels withdrawal_pending_review enabled", true);
										$player_risk_score_level = $this->risk_score_model->getPlayerCurrentRiskLevel($playerId);
										$this->utils->debug_log("risk_score_levels player_risk_score_level value", $player_risk_score_level);
										$this->utils->debug_log("risk_score_levels risk_score value", $value['risk_score']);
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
			}
		}

		$this->utils->debug_log("risk_score_levels walletAccountData", $walletAccountData['dwStatus']);

		//transfer back balance by feature
		$rlt= $this->_transferBackToMainWallet($playerId, $player['username']);
		$this->utils->debug_log('after _transferBackToMainWallet, result', $rlt);
		if(!$rlt){

			$msg=lang('Transfer Failed From Sub-wallet');
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => $msg,
			));
			return redirect($redirectPage);
		}

		if($this->utils->getConfig('enable_pending_review_custom')){
			$getWithdrawalCustomSetting = json_decode($this->operatorglobalsettings->getSetting('custom_withdrawal_processing_stages')->template,true);

			if(!empty($getWithdrawalCustomSetting['pendingCustom']) && $getWithdrawalCustomSetting['pendingCustom']['enabled']){
				if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId)){
					$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
					$this->utils->debug_log("check player tag under pending custom walletAccountData", $walletAccountData['dwStatus']);
				}
			}
		}



        $walletAccountData['amount']=$amount;
		$beforeBalance = $this->wallet_model->getMainWalletBalance($playerId);//$this->wallet_model->getTargetTransferBalanceOnMainWallet($playerId, Wallet_model::MAIN_WALLET_ID);
		$walletAccountData['before_balance']=$beforeBalance;
		$walletAccountData['after_balance']=$beforeBalance - $amount;

        #cryptocurrency withdrawal

		$withdrawal_crypto_currency = $this->config->item('enable_withdrawal_crypto_currency');

        if($this->utils->isCryptoCurrency($banktype)){
        	$cryptocurrency  = $this->utils->getCryptoCurrency($banktype);
        	$defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];
        	$data['custCryptoInputDecimalPlaceSetting'] = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency);
        	$reciprocalDecimalPlaceSetting = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
        	$crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
            $custom_withdrawal_fee = $this->config->item('custom_withdrawal_fee') ? $this->config->item('custom_withdrawal_fee') : 0;
            $cust_crypto_allow_compare_digital = $this->utils->getCustCryptoAllowCompareDigital($cryptocurrency);
            list($crypto, $rate) = $this->utils->convertCryptoCurrency($amount, $defaultCurrency, $cryptocurrency,'withdrawal');
            $crypto = $this->input->post('cryptoQty');
            $this->load->library('session');
            $request_cryptocurrency_rate = json_decode($this->session->userdata('cryptocurrencies_rates'), true);
            $session_rate = '';
            $cryptoQty = $this->input->post('cryptoQty');
            $this->session->unset_userdata('cryptocurrencies_rates');
            if(is_array($request_cryptocurrency_rate) && !empty($request_cryptocurrency_rate)){
            	foreach ($request_cryptocurrency_rate as $key => $value) {
	            	if($key == $cryptocurrency){
	            		$session_rate = $value;
	            	}
            	}
            }
            $this->utils->debug_log('---withdrawal session crypto rate and current crypto rate--- ', $cryptocurrency, $session_rate, $rate);
            if(!empty($rate) && !empty($session_rate)){
                if(abs($rate - $session_rate) > $cust_crypto_allow_compare_digital){
                    $this->session->set_flashdata('result', array(
						'success' => false,
						'message' => lang('The crypto rate is not in allow compare range')
					));
					return redirect($redirectPage);
                }
                $rate = $session_rate;
            }
            if($crypto != number_format($amount * $crypto_to_currecny_exchange_rate/ $rate, $reciprocalDecimalPlaceSetting,'.','')){
        		$this->utils->debug_log("The conversion result is not correct",$rate,$crypto,$amount);
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('The conversion result is not correct')
				));
				return redirect($redirectPage);
        	}
            $data['cryptocurrency_rate'] = $rate;
            $data['currency_conversion_rate'] = (1/$rate);
            $data['custCryptoUpdateTiming'] = $this->utils->getCustCryptoUpdateTiming($cryptocurrency);

            $custom_withdrawal_rate = $this->config->item('custom_withdrawal_rate') ? $this->config->item('custom_withdrawal_rate') : 1;
            $player_rate = number_format($rate   * $custom_withdrawal_rate, 4, '.', '');

            if ($withdrawal_crypto_currency['enabled']) {
				list($reverseRate,$exchangeRate) = $this->callCryptoRate($cryptocurrency,$defaultCurrency,$withdrawal_crypto_currency);

				if (!empty($reverseRate) && !empty($exchangeRate)) {
					$rate = $reverseRate;
					$crypto = number_format($amount / $exchangeRate, 8, '.', '');
				}
            }
            $withdrawal_notes = 'Wallet Address: '.$walletAccountData['bankAccountNumber'].' | '.$cryptocurrency.': '.$crypto.' | Crypto Real Rate: '.$rate;

            $walletAccountData['notes'] = $withdrawal_notes;
            $walletAccountData['extra_info'] = $crypto;
            $this->utils->debug_log('=======================cryptocurrency withdrawal_notes', $withdrawal_notes);
        }

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

		$success = $this->lockAndTransForPlayerBalance($playerId, function () use ($walletModel, $playerId, $walletAccountData, $fee, $cryptoSet, $cryptocurrency, $rate, $playerRateSet, &$walletAccountId, &$hasSufficientBalance, &$errorMsg, $calculationFormula, $withdrawFeeAmount,$withdrawal_notes, $withdrawBankFeeAmount, $calculationFormulaBank) {

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
				$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center, status => request");
				if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
					$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center, status => request ; %s ; Withdrawal Fee Amount is %s", $calculationFormula, $withdrawFeeAmount);
				}

				if($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)){
					if ($withdrawBankFeeAmount > 0) {
						$withdrawalActionLogByPlayer = $withdrawalActionLogByPlayer . ' ' . $calculationFormulaBank;
					}
				}

				$walletModel->addWalletaccountNotes($walletAccountId, $playerId, $withdrawalActionLogByPlayer, $walletAccountData['dwStatus'], null, Walletaccount_timelog::PLAYER_USER);

				if(!empty($walletAccountId) && ($cryptoSet != false) && ($playerRateSet != false)) {
					$adminUserId = $this->users->getSuperAdminId();
					$lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
					$crypto_withdrawal_order_id = $walletModel->createCryptoWithdrawalOrder($walletAccountId, $cryptoSet, $rate, $this->utils->getNowForMysql(), $this->utils->getNowForMysql(),$cryptocurrency);
				}
			}

			return !empty($walletAccountId);
		});


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

		$this->utils->debug_log("Withdrawal submitted by player [$playerId] evaluated, amount [$amount], hasSufficientBalance [$hasSufficientBalance], walletAccountId [$walletAccountId], errorMsg [$errorMsg]");

		if(!$hasSufficientBalance) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => empty($errorMsg) ? lang('notify.55') : $errorMsg
			));
			redirect($redirectPage);
		}

		$this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);
		if(!$success) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('error.withdrawal_failed')
			));
			redirect($redirectPage);
		}

		$withdrawSuccessMsg = lang($this->config->item('playercenter.withdrawMsg.success') ?: 'notify.58');

		$this->session->set_flashdata('result', array(
			'success' => true,
			'message' => $withdrawSuccessMsg." <!-- walletAccountId: [$walletAccountId] -->"
		));

        $walletAccountData['walletAccountId'] = $walletAccountId;
        if($this->utils->getConfig('enable_fast_track_integration')) {
            $this->load->library('fast_track');
            $this->fast_track->requestWithdraw($walletAccountData);
        }

        #OGP-22453,22538
        if ($this->utils->getConfig('enabled_withdrawal_abnormal_notification')) {
			if($success){
				$userId = $this->users->getSuperAdminId();
				$this->triggerWithdrawalEvent($playerId, $walletAccountId, null, null, $userId);
			}
        }

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

		$third_api_id = $this->utils->getConfig('third_party_api_id_when_withdraw');
        if (!empty($third_api_id) && !empty($walletAccountId)) {
			$api = $this->utils->loadExternalSystemLibObject($third_api_id);
			if (!empty($api)) {
				$redirectPay4FunVerify = $api->submitPay4FunVerify($playerId, $redirectPage, $walletAccountData['amount'], $walletAccountId);
				if(is_array($redirectPay4FunVerify) && $redirectPay4FunVerify['status']){
					$withdrawal_notes = "Pay4Fun Verify Status: Not yet verified.";
					$adminUserId = $this->users->getSuperAdminId();
					$lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
					redirect($redirectPay4FunVerify['url']);
				}
			}
        }

		redirect($redirectPage);
	}

	#verify_custom_withdrawal form submits to this action
	public function verify_custom_withdrawal() {
		$this->load->model(['playerbankdetails','risk_score_model', 'banktype', 'player_promo', 'transactions', 'users', 'wallet_model', 'sms_verification']);

		$playerId = $this->load->get_var('playerId');
		$player = $this->load->get_var('player');
		$this->utils->debug_log('verify playerId', $playerId, 'player', $player, 'input post', $this->input->post());
		$type = $this->input->post('custom_type');

		if(!$this->checkAgencyCreditMode($playerId)){
            return;
        }

		if (!in_array($type, self::BANK_TYPE_ARR)) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('withdrawal_bank_type_not_exist')
			));

			return redirect('player_center2/withdraw');
		}

		$redirectPage = 'player_center2/withdraw'.'/'.$type;

		if ($player['enabled_withdrawal'] == self::WITHDRAWAL_DISABLED) {
			$this->utils->debug_log("Withdrawal attempted when withdrawal is disabled.");
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('withdrawal_disabled_message')
			));
			return redirect($redirectPage);
		}

		if ($this->utils->isEnabledFeature("show_allowed_withdrawal_status") && $this->utils->isEnabledFeature("show_risk_score") && $this->utils->isEnabledFeature("show_kyc_status")) {
			if(!$this->risk_score_model->generate_allowed_withdrawal_status($playerId)){
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('not_allowed_kyc_risk_score_message')
				));
				return redirect($redirectPage);
			}
		}

		if(!$this->verifyAndResetDoubleSubmit($playerId)){

			$message = lang('Please refresh and try, and donot allow double submit');
			if( $this->input->is_ajax_request() ){
				$this->returnJsonResult(array('status' => 'error', 'msg' => $message));
				return;
			}
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => $message
			));
			redirect($redirectPage);
			return;

		}

		if($this->utils->getConfig('check_sub_wallect_balance_in_withdrawal')){
			$balanceDetails = $this->wallet_model->getBalanceDetails($playerId);
			if(is_array($balanceDetails) && !empty($balanceDetails)){
				foreach ($balanceDetails['sub_wallet'] as $subWallet) {
					if($subWallet['totalBalanceAmount'] < 0){
						$message = sprintf(lang('SubWallet with negative balance'),$subWallet['game'],$subWallet['totalBalanceAmount']);
						$this->session->set_flashdata('result', array(
							'success' => false,
							'message' => $message
						));
						redirect($redirectPage);
						return;
					}
				}
			}else{
				$message = lang('check subwallet balance failed');
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => $message
				));
				redirect($redirectPage);
				return;
			}
		}

$bankDetailsId = $this->input->post('bankDetailsId');

		if(empty($bankDetailsId)) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_bank_details_empty'));
			$this->utils->debug_log("=====================bankDetailsId", $bankDetailsId);
			redirect($redirectPage);
		}else {
			# Record down which id is used, display back the same id
			$this->session->set_flashdata('customBankDetailsId', $bankDetailsId);
		}

		# SMS and Password verification is not yet present in new player center. Verify withdraw password only
		$withdrawVerificationMethod = $this->utils->getConfig('withdraw_verification');

		$this->utils->debug_log("=====================withdrawVerificationMethod", $withdrawVerificationMethod);
		if($withdrawVerificationMethod == 'withdrawal_password'){
			$withdrawal_password = $this->input->post('withdrawal_password');
			$checkWithdrawPassword=$this->player_model->validateWithdrawalPassword($playerId, $withdrawal_password);
			if(!$checkWithdrawPassword){
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Invalid Withdrawal Password')
				));
				redirect($redirectPage);
			}
		}

		#sms verify in withdraw page
		if ($this->utils->getConfig('enable_sms_verify_in_withdraw')) {
			$contact_number = $this->player_model->getPlayerContactNumber($playerId);
			$sms_verification_code = $this->input->post('sms_verification_code');

			$this->utils->debug_log("enable_sms_verify_in_withdraw", $contact_number, $sms_verification_code);

			$verify_result = [];
			$usage = null;
			if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
				$usage = 'sms_api_withdrawal_setting';
			}

			$res = $this->update_sms_verification($contact_number,$sms_verification_code, $usage, $verify_result);
			$this->utils->debug_log("enable_sms_verify_in_withdraw res", $res);

			if(!$res['success']){
				$session_id = $this->session->userdata('session_id');
				$smsValidateResult = $this->sms_verification->validateSmsVerifiedStatus($playerId, $session_id, $contact_number, $sms_verification_code, $usage);

				$this->utils->debug_log('------------validateSmsVerifiedStatus smsValidateResult',$smsValidateResult);
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => $res['message']
				));
				redirect($redirectPage);
			}
		}

		# Validate withdraw limits
		$amount = $this->input->post('amount');
		$this->utils->debug_log("Withdrawal submitted by player [$playerId], amount [$amount], bankDetailsId [$bankDetailsId]");

		if($this->CI->config->item('disable_withdraw_amount_is_decimal')){
			if (is_numeric( $amount ) && floor( $amount ) != $amount) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.118')
				));
				redirect($redirectPage);
			}
		}
		// Verify if the submittng bankDetailsId belong to thie player
		$check_if_bank_detail_belnog_to_player = false;
		$check_bank_detail_enabled_withdrawal_to_player = false;
		$available_banks = $this->playerbankdetails->getAvailableWithdrawBankDetail($playerId);
		$this->utils->debug_log("=====================available_banks", $available_banks);
		if($available_banks == NULL) {
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_bank_details_null'));
			redirect($redirectPage);
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
			$this->utils->debug_log("=====================Verify player bank details error. The bankDetailsId: [$bankDetailsId] is not belong to the playerId: [$playerId].");
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_bank_details_binding_error'));
			redirect($redirectPage);
		}

		if(!$check_bank_detail_enabled_withdrawal_to_player) {
			$this->utils->debug_log("=====================Financial Institution bank account is disable");
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('notify.verify_player_Financial_account_enable'));
			redirect($redirectPage);
		}

		## Check whether withdrawal satisfies withdrawal rule for this player
        $playerWithdrawalRule = $this->utils->getWithdrawMinMax($playerId);
		list($withdrawalProcessingCount, $processingAmount) = $this->wallet_model->countTodayProcessingWithdraw($playerId);
		list($withdrawalPaidCount, $paidAmount) = $this->transactions->count_today_withdraw($playerId);
		$amount_used = $processingAmount + $paidAmount;
		$time_used = $withdrawalProcessingCount + $withdrawalPaidCount;

		if (!$playerWithdrawalRule || is_null($playerWithdrawalRule['max_withdraw_per_transaction'])) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('Undefined withdraw rule')
			));
			redirect($redirectPage);
		}
		else if ($amount > $playerWithdrawalRule['max_withdraw_per_transaction']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('Max Withdrawal Per Transaction is').$playerWithdrawalRule['max_withdraw_per_transaction']
			));
			redirect($redirectPage);
		}
		if ($amount + $amount_used > $playerWithdrawalRule['daily_max_withdraw_amount']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('notify.56')
			));
			redirect($redirectPage);
		}
		if ($time_used >= $playerWithdrawalRule['withdraw_times_limit']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('notify.106')
			));
			redirect($redirectPage);
		}

		if ($amount < $playerWithdrawalRule['min_withdraw_per_transaction']) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('notify.102') . $playerWithdrawalRule['min_withdraw_per_transaction']
			));
			redirect($redirectPage);
		}

		#check  withdrawal conditions
		if ($this->utils->isEnabledFeature('check_withdrawal_conditions')) {
            if(FALSE !== $un_finished = $this->withdraw_condition->getPlayerUnfinishedWithdrawCondition($playerId)){
                //un_finished_withdrawal
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.withdrawal.condition')
				));
                redirect($redirectPage);
            }
		}

		#check  withdrawal conditions -- for each
		if ($this->utils->isEnabledFeature('check_withdrawal_conditions_foreach')) {
			if( FALSE !== $withdraw_data = $this->withdraw_condition->getPlayerUnfinishedWithdrawConditionForeach($playerId)){
                //un_finished
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.withdrawal.condition')
				));
                redirect($redirectPage);
            }
		}

		##check deposit conditions in withdrawal conditions -- for each
        if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')){
            if(FALSE !== $un_finished_deposit = $this->withdraw_condition->getPlayerUnfinishedDepositConditionForeach($playerId)){
                //un_finished_deposit
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('notify.withdrawal.condition')
				));
                redirect($redirectPage);
            }
        }

		## Check whether multiple pending withdraws are allowed
		$this->load->model('group_level');
		if ($this->group_level->isOneWithdrawOnly($playerId)) {
			$this->load->model('player_model');
			if ($this->player_model->countWithdrawByStatusList($playerId) >= 1) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Sorry, your last withdraw request is not done, so you can\'t start new request')
				));
				redirect($redirectPage);
			}
		}

		#OGP-23514 Check Daily open withdrawal time
		$enable_withdrawal_period = $this->utils->getConfig('enable_withdrawal_period');
		if (!empty($enable_withdrawal_period['start_at']) && !empty($enable_withdrawal_period['end_at'])) {
			$currentDateTime = new DateTime();
			$currentDate = $this->utils->formatDateForMysql($currentDateTime);
			$currentServertime = $this->utils->formatDateTimeForMysql($currentDateTime);
			$withdrawal_start_time = $currentDate . ' ' . $enable_withdrawal_period['start_at'] . ':00:00';
			$withdrawal_end_time = $currentDate . ' ' . $enable_withdrawal_period['end_at'] . ':00:00';
			$this->utils->debug_log(__METHOD__,'currentDateTime',$currentDateTime,$currentDate,$currentServertime,$withdrawal_start_time,$withdrawal_end_time);

			if (strtotime($currentServertime) < strtotime($withdrawal_start_time) || strtotime($currentServertime) > strtotime($withdrawal_end_time)) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Your application is not within the withdrawal period')
				));
				redirect($redirectPage);
			}
		}

		# All checks done, we can now proceed to submit withdrawal request
		## Save bank info if it's new (no bankDetailsId given)
		if(!$bankDetailsId) {
			$data = array(
				'playerId' => $playerId,
				'bankTypeId' => $this->input->post('bankTypeId'),
				'bankAccountNumber' => $this->input->post('bankAccNum'),
				'bankAccountFullName' => $this->input->post('bankAccName'),
				'bankAddress' => $this->input->post('bankAddress'),
				'province' => $this->input->post('province'),
				'city' => $this->input->post('city'),
				'branch' => $this->input->post('branch'),
				'isRemember' => Playerbankdetails::REMEMBERED,
				'dwBank' => Playerbankdetails::WITHDRAWAL_BANK,
				'verified' => '1',
				'status' => Playerbankdetails::STATUS_ACTIVE,
				'phone' => $this->input->post('phone'),
			);

	        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_default_unverified')){
	            $data['verified'] = '0';
	        }

			if(empty($data['bankTypeId'])) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Sorry, please specify receiving account information')
				));
				redirect($redirectPage);
			}
			$this->load->library('player_functions');
			$bankDetailsId = $this->player_functions->addBankDetailsByWithdrawal($data);
			# Again, record down which id is used, display back the same id
			$this->session->set_flashdata('customBankDetailsId', $bankDetailsId);
		}

		$playerAccount = $this->player_model->getPlayerAccountByPlayerIdOnly($playerId);
		$dwIp = $this->utils->getIP();
		$geolocation = $this->utils->getGeoplugin($dwIp);

		# Get bank details
		$playerBankDetails = $this->playerbankdetails->getBankList(array('playerBankDetailsId' => $bankDetailsId))[0];

        $banktype = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId']);
		$bankName = $banktype->bankName;

		$this->load->library('payment_library');
		$withdrawFeeAmount = 0;
		$calculationFormula = '';
		#if enable config get withdrawFee from player
		if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
			list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player['levelId'], $amount);

			$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);
			$this->utils->debug_log('enable_withdrawl_fee_from_player' , $mainAmount, $amount, $withdrawFeeAmount, $calculationFormula);
			if ($amount + $withdrawFeeAmount > $mainAmount) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance')
				));
				redirect($redirectPage);
			}
		}

		$withdrawBankFeeAmount = 0;
		$calculationFormulaBank = '';
		if ($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)) {
			list($withdrawBankFeeAmount,$calculationFormulaBank) = $this->payment_library->calculationWithdrawalBankFee($playerId, $banktype->bank_code, $amount);

			$mainAmount = $this->wallet_model->getMainWalletBalance($playerId);

			if ($withdrawFeeAmount > 0) {
				$checkFeeAmt = $amount + $withdrawBankFeeAmount + $withdrawFeeAmount;
			}else{
				$checkFeeAmt = $amount + $withdrawBankFeeAmount;
			}

			$this->utils->debug_log('enable_withdrawl_bank_fee' , $mainAmount, $amount, $withdrawBankFeeAmount, $calculationFormulaBank);
			if ($checkFeeAmt > $mainAmount) {
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('Withdrawal Amount + Withdrawal fee is greater than Current Balance')
				));
				redirect($redirectPage);
			}
		}

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
			'withdrawal_bank_fee' => $withdrawBankFeeAmount,
		);

		# OGP-3531
		if($this->utils->isEnabledFeature('enable_withdrawal_pending_review')){
			if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId)){
				$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
			}

			# OGP-3996 // add withdrawal_pending_review to risk score chart and set 1 = true unset if false in System settings->Risk score setting
			if($this->utils->isEnabledFeature('enable_withdrawal_pending_review_in_risk_score') && $this->utils->isEnabledFeature('show_risk_score')) {
				$risk_score_levels = $this->risk_score_model->getRiskScoreInfo(risk_score_model::RC);
				$this->utils->debug_log("risk_score_levels enable_withdrawal_pending_review_in_risk_score", "enabled");
				if(!empty($risk_score_levels)){
					$this->utils->debug_log("risk_score_levels not empty", true);
					if(isset($risk_score_levels['rules'])){
						$this->utils->debug_log("risk_score_levels rules set", true);
						$rules = json_decode($risk_score_levels['rules'],true);
						if(!empty($rules)) {
							$this->utils->debug_log("risk_score_levels rules not empty", true);
							foreach ($rules as $key => $value) {
								if(isset($value['withdrawal_pending_review'])){
									$this->utils->debug_log("risk_score_levels withdrawal_pending_review set", true);
									if($value['withdrawal_pending_review']){
										$this->utils->debug_log("risk_score_levels withdrawal_pending_review enabled", true);
										$player_risk_score_level = $this->risk_score_model->getPlayerCurrentRiskLevel($playerId);
										$this->utils->debug_log("risk_score_levels player_risk_score_level value", $player_risk_score_level);
										$this->utils->debug_log("risk_score_levels risk_score value", $value['risk_score']);
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
			}
		}

		$this->utils->debug_log("risk_score_levels walletAccountData", $walletAccountData['dwStatus']);

		//transfer back balance by feature
		$rlt= $this->_transferBackToMainWallet($playerId, $player['username']);
		$this->utils->debug_log('after _transferBackToMainWallet, result', $rlt);
		if(!$rlt){

			$msg=lang('Transfer Failed From Sub-wallet');
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => $msg,
			));
			return redirect($redirectPage);
		}

		if($this->utils->getConfig('enable_pending_review_custom')){
			$getWithdrawalCustomSetting = json_decode($this->operatorglobalsettings->getSetting('custom_withdrawal_processing_stages')->template,true);

			if(!empty($getWithdrawalCustomSetting['pendingCustom']) && $getWithdrawalCustomSetting['pendingCustom']['enabled']){
				if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId)){
					$walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
					$this->utils->debug_log("check player tag under pending custom walletAccountData", $walletAccountData['dwStatus']);
				}
			}
		}



        $walletAccountData['amount']=$amount;
		$beforeBalance = $this->wallet_model->getMainWalletBalance($playerId);//$this->wallet_model->getTargetTransferBalanceOnMainWallet($playerId, Wallet_model::MAIN_WALLET_ID);
		$walletAccountData['before_balance']=$beforeBalance;
		$walletAccountData['after_balance']=$beforeBalance - $amount;

        #cryptocurrency withdrawal

		$withdrawal_crypto_currency = $this->config->item('enable_withdrawal_crypto_currency');

        if($this->utils->isCryptoCurrency($banktype)){
			$cryptocurrency  = $this->utils->getCryptoCurrency($banktype);
			$defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];
			$data['custCryptoInputDecimalPlaceSetting'] = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency);
			$reciprocalDecimalPlaceSetting = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
			$crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
		    $custom_withdrawal_fee = $this->config->item('custom_withdrawal_fee') ? $this->config->item('custom_withdrawal_fee') : 0;
            $cust_crypto_allow_compare_digital = $this->utils->getCustCryptoAllowCompareDigital($cryptocurrency);
            list($crypto, $rate) = $this->utils->convertCryptoCurrency($amount, $defaultCurrency, $cryptocurrency,'withdrawal');
            $crypto = $this->input->post('cryptoQty');
            $this->load->library('session');
            $request_cryptocurrency_rate = json_decode($this->session->userdata('cryptocurrencies_rates'), true);
            $session_rate = '';
            $cryptoQty = $this->input->post('cryptoQty');
            $this->session->unset_userdata('cryptocurrencies_rates');
			if(is_array($request_cryptocurrency_rate) && !empty($request_cryptocurrency_rate)){
				foreach ($request_cryptocurrency_rate as $key => $value) {
					if($key == $cryptocurrency){
						$session_rate = $value;
					}
				}
			}
            $this->utils->debug_log('---withdrawal session crypto rate and current crypto rate--- ', $cryptocurrency, $session_rate, $rate);
            if(!empty($rate) && !empty($session_rate)){
                if(abs($rate - $session_rate) > $cust_crypto_allow_compare_digital){
                    $this->session->set_flashdata('result', array(
						'success' => false,
						'message' => lang('The crypto rate is not in allow compare range')
					));
					return redirect($redirectPage);
                }
                $rate = $session_rate;
            }
            if($crypto != number_format($amount * $crypto_to_currecny_exchange_rate/ $rate, $reciprocalDecimalPlaceSetting,'.','')){
				$this->utils->debug_log("The conversion result is not correct",$rate,$crypto,$amount);
				$this->session->set_flashdata('result', array(
					'success' => false,
					'message' => lang('The conversion result is not correct')
				));
				return redirect($redirectPage);
			}
            $data['cryptocurrency_rate'] = $rate;
            $data['currency_conversion_rate'] = (1/$rate);
            $data['custCryptoUpdateTiming'] = $this->utils->getCustCryptoUpdateTiming($cryptocurrency);

            $custom_withdrawal_rate = $this->config->item('custom_withdrawal_rate') ? $this->config->item('custom_withdrawal_rate') : 1;
            $player_rate = number_format($rate   * $custom_withdrawal_rate, 4, '.', '');

            if ($withdrawal_crypto_currency['enabled']) {
				list($reverseRate,$exchangeRate) = $this->callCryptoRate($cryptocurrency,$defaultCurrency,$withdrawal_crypto_currency);

				if (!empty($reverseRate) && !empty($exchangeRate)) {
					$rate = $reverseRate;
					$crypto = number_format($amount / $exchangeRate, 8, '.', '');
				}
            }
            $withdrawal_notes = 'Wallet Address: '.$walletAccountData['bankAccountNumber'].' | '.$cryptocurrency.': '.$crypto.' | Crypto Real Rate: '.$rate;

            $walletAccountData['notes'] = $withdrawal_notes;
            $walletAccountData['extra_info'] = $crypto;
            $this->utils->debug_log('=======================cryptocurrency withdrawal_notes', $withdrawal_notes);
        }

		if (!empty($this->utils->getConfig('enabled_withdrawal_custom_page'))) {

			$w_country = $this->input->post('country');
			$w_converted_amount = $this->input->post('withdrawal_conversion_amt');
			$fx_rate = '';

			if (!empty($w_country) && $w_converted_amount > 0) {
				$res = $this->checkConversionRes($w_country, $w_converted_amount);
				$this->utils->debug_log('=======================checkConversionRes res', $res, $amount);
				if (isset($res['fx_rate'])) {
					$fx_rate = $res['fx_rate'];
					$rate_to_currency = number_format((1 / $fx_rate), '5');
					$converted_amount = number_format($rate_to_currency * $w_converted_amount, '2');
					$this->utils->debug_log('=======================checkConversionRes fx_rate', $fx_rate, 'rate_to_currency', $rate_to_currency, 'converted_amount', $converted_amount);
					if (abs($amount - $converted_amount) > 1) {
						$w_msg = "The origination amount does not match the converted amount";
						$this->utils->debug_log($w_msg, $res, $amount);
						$this->session->set_flashdata('result', array(
							'success' => false,
							'message' => lang($w_msg)
						));
						return redirect($redirectPage);
					}
				}else{
					$w_msg = "Get exchange rates failed";
					$this->utils->debug_log($w_msg, $res, $amount);
					$this->session->set_flashdata('result', array(
						'success' => false,
						'message' => lang($w_msg)
					));
					return redirect($redirectPage);
				}

				$withdrawal_notes = $w_country .': '. $w_converted_amount . ' | Rate: '. $fx_rate;
				$walletAccountData['notes'] = $withdrawal_notes;
			}

			$extra_data = array(
				'country' => $this->input->post('country'),
				'currency' => $this->input->post('currency'),
				'bank_code' => $this->input->post('bank_code'),
				'document_type' => $this->input->post('document_type'),
				'document_id' => trim($this->input->post('document_id',1)),
				'account_type' => $this->input->post('account_type'),
				'bank_account' => trim($this->input->post('bank_account',1)),
				'converted_amount' => $this->input->post('withdrawal_conversion_amt')
			);
			$walletAccountData['extra_info'] = json_encode($extra_data);
		}

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

		$success = $this->lockAndTransForPlayerBalance($playerId, function () use ($walletModel, $playerId, $walletAccountData, $fee, $cryptoSet, $cryptocurrency, $rate, $playerRateSet, &$walletAccountId, &$hasSufficientBalance, &$errorMsg, $calculationFormula, $withdrawFeeAmount,$withdrawal_notes, $withdrawBankFeeAmount, $calculationFormulaBank) {

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
				$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center, status => request");
				if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
					$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center, status => request ; %s ; Withdrawal Fee Amount is %s", $calculationFormula, $withdrawFeeAmount);
				}

				if($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)){
					if ($withdrawBankFeeAmount > 0) {
						$withdrawalActionLogByPlayer = $withdrawalActionLogByPlayer . ' ' . $calculationFormulaBank;
					}
				}

				$walletModel->addWalletaccountNotes($walletAccountId, $playerId, $withdrawalActionLogByPlayer, $walletAccountData['dwStatus'], null, Walletaccount_timelog::PLAYER_USER);

				if(!empty($walletAccountId) && ($cryptoSet != false) && ($playerRateSet != false)) {
					$adminUserId = $this->users->getSuperAdminId();
					$lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
					$crypto_withdrawal_order_id = $walletModel->createCryptoWithdrawalOrder($walletAccountId, $cryptoSet, $rate, $this->utils->getNowForMysql(), $this->utils->getNowForMysql(),$cryptocurrency);
				}
			}

			return !empty($walletAccountId);
		});


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

		$this->utils->debug_log("Withdrawal submitted by player [$playerId] evaluated, amount [$amount], hasSufficientBalance [$hasSufficientBalance], walletAccountId [$walletAccountId], errorMsg [$errorMsg]");

		if(!$hasSufficientBalance) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => empty($errorMsg) ? lang('notify.55') : $errorMsg
			));
			redirect($redirectPage);
		}

		$this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);
		if(!$success) {
			$this->session->set_flashdata('result', array(
				'success' => false,
				'message' => lang('error.withdrawal_failed')
			));
			redirect($redirectPage);
		}

		$withdrawSuccessMsg = lang($this->config->item('playercenter.withdrawMsg.success') ?: 'notify.58');

		$this->session->set_flashdata('result', array(
			'success' => true,
			'message' => $withdrawSuccessMsg." <!-- walletAccountId: [$walletAccountId] -->"
		));

        $walletAccountData['walletAccountId'] = $walletAccountId;
        if($this->utils->getConfig('enable_fast_track_integration')) {
            $this->load->library('fast_track');
            $this->fast_track->requestWithdraw($walletAccountData);
        }

        #OGP-22453,22538
        if ($this->utils->getConfig('enabled_withdrawal_abnormal_notification')) {
			if($success){
				$userId = $this->users->getSuperAdminId();
				$this->triggerWithdrawalEvent($playerId, $walletAccountId, null, null, $userId);
			}
        }

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

			if ($mobileNumIsVeridied && $isUseQueueToSend) {
				$this->lib_queue->addRemoteSMSJob($mobileNum, $smsContent, $callerType, $caller, $state, null);
			} else if ($mobileNumIsVeridied) {
				$this->sms_sender->send($mobileNum, $smsContent);
			}
		}

		$third_api_id = $this->utils->getConfig('third_party_api_id_when_withdraw');
        if (!empty($third_api_id) && !empty($walletAccountId)) {
            $api = $this->utils->loadExternalSystemLibObject($third_api_id);
            if (!empty($api)) {
            	$redirectPay4FunVerify = $api->submitPay4FunVerify($playerId, $redirectPage, $walletAccountData['amount'], $walletAccountId);
            	if(is_array($redirectPay4FunVerify) && $redirectPay4FunVerify['status']){
            		$withdrawal_notes = "Pay4Fun Verify Status: Not yet verified.";
            		$adminUserId = $this->users->getSuperAdminId();
            		$lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
            		redirect($redirectPay4FunVerify['url']);
            	}
            }
        }

		redirect($redirectPage);
	}

	public function checkConversionRes($country, $amount){
		$this->load->library('payment_library');
        $this->utils->debug_log(__METHOD__, $country, $amount);
		$enabled_withdrawal_custom_page = $this->utils->getConfig('enabled_withdrawal_custom_page')[$this->utils->getConfig('custom_lang')];
	    $exchange_rates_url             = $enabled_withdrawal_custom_page['exchange_rates_url'];
	    $header_readonly_key			= $enabled_withdrawal_custom_page['header_readonly_key'];
		$params 						= [];
		$headers 						= [];
		$isPost 						= false;

		if ($header_readonly_key) {
			$headers = [$header_readonly_key];
		}

		$params = array('country' => $country, 'amount' => $amount);
		$rateData = $this->payment_library->paymentHttpCall($exchange_rates_url, $params, $isPost, false ,$headers);
		$rateData = json_decode($rateData, true);
        $this->utils->debug_log(__METHOD__,'rateData', $rateData);
        return $rateData;

	}

	public function callCryptoRate($cryptocurrency,$defaultCurrency,$withdrawal_crypto_currency){
		$this->load->library('payment_library');

		$crypto_rate_url  = 'https://service-api.paymero.io/v1/crypto/rate';
        $params['ticker'] = $cryptocurrency.'_'.$defaultCurrency;
        $params['amount'] = 1;
        $params['action'] = 'sell';
        $headers = [
            'Content-Type: application/json',
            'X-Api-Key: '. $withdrawal_crypto_currency['api_key']
        ];

        $result = $this->payment_library->paymentHttpCall($crypto_rate_url,$params,true,true,$headers);
        $rateData = json_decode($result);
        $this->utils->debug_log('=======================cryptocurrency rateData', $rateData);
        if (isset($rateData->status)) {
			if ($rateData->status == 'success') {
				return array($rateData->data->reverseRate,$rateData->data->exchangeRate);
			} else {
				return array(null,null);
			}
		}
	}

	protected function checkAgencyCreditMode($playerId){
        $this->load->model(['player_model', 'wallet_model']);

        $player=$this->player_model->getPlayerById($playerId);

        if ($this->utils->isEnabledFeature('agent_player_cannot_use_deposit_withdraw') && $player && $player->credit_mode) {
            if ($this->wallet_model->isPlayerWalletAccountZero($playerId)) {
                $this->player_model->disabledCreditMode($playerId);
                // $this->player_model->updatePlayer($playerId, array('credit_mode'=> FALSE));
            } else {
                // show_error('credit mode is on, deposit is disabled');

                $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('credit mode is on, deposit/withdraw is not allowed'));
                $this->goPlayerHome();
                return false;
            }
        }

        return true;
    }

    public function view_withdraw_condition_detail(){
        if(!$this->utils->getConfig('show_wc_detail_in_cashier_withdrawal')){
            return redirect('player_center2/withdraw');
        }

        $playerId = $this->load->get_var('playerId');

        $res = $this->withdraw_condition->computePlayerWithdrawalConditionsWithDepositCondition($playerId);
        if(!empty($res)){
            $wd_summary = [
                'required_bet'		=> $res['totalRequiredBet'] ,
                'current_total_bet'	=> $res['totalPlayerBet'] ,
                'unfinished_bet'		=> $res['unfinished_bet'],
                'unfinished_deposit'    => $res['unfinished_deposit']
            ];

            $data['wc'] = $wd_summary;
        }

        # Render
        $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/cashier/withdraw/player_withdraw_condition_details', $data);
    }

    /**
	 * update phone verification flag
	 *
	 * @param  string $contact_number
	 * @param  string $sms_verification_code
	 * @return json
	 */
	public function update_sms_verification($contact_number, $sms_verification_code, $restrict_area = null, &$result = []) {

        if(!$this->checkBlockPlayerIPOnly()){
            return false;
        }
		$playerId = $this->authentication->getPlayerId();
		// if (static::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'update_sms_verification')) {
		// 	return $this->_show_last_check_acl_response('json');
		// }
		$this->load->library('session');
		$this->load->model(['sms_verification','player_model']);

		$session_id = $this->session->userdata('session_id');

		$success = false;
		$message = lang('Verify SMS Code Failed');

		$player = $this->player_functions->getPlayerById($playerId);

		if ($contact_number == 'verified') {
			# Try to get mobile number from player profile if not supplied
			$this->load->library(array('authentication', 'player_functions'));
			$contact_number = $player['contactNumber'];
			if (!$contact_number) {
				$message=lang('Verify SMS Code Failed');
			}
		}
		if(!empty($playerId) && !empty($session_id) && !empty($contact_number) && !empty($sms_verification_code)){

			$success = !isset($sms_verification_code) || $this->sms_verification->validateVerificationCode($playerId, $session_id, $contact_number, $sms_verification_code, $restrict_area);

			if(!$success) {
				$this->utils->debug_log('========== validate sms_verification_code from back office =====', $success);
				// validte verification code from back office
				$success = $this->sms_verification->validateVerificationCode($playerId, null, $contact_number, $sms_verification_code);
			}
			$this->utils->debug_log('========== sms_verification_code result =====', $success);
			if($success){
				$success=$this->player_model->updateAndVerifyContactNumber($playerId, $contact_number);
				if(!$success){
					$message=lang('Verify SMS Code Failed');
				}
			}else{
				$message=lang('Verify SMS Code Failed');
			}

			if($success){
				$username= $this->authentication->getUsername();
				$this->savePlayerUpdateLog($playerId, lang('Phone verified by player: ') . ' ' . $username, $username);
				$message=lang('Verify SMS Code Successfully');

				$this->syncPlayerCurrentToMDBWithLock($playerId, $username, false);
			}
		}

		$result = ['success'=>$success, 'message'=>$message];

		return $result;
	}

	/**
	 * Personal Information Update History
	 * @author kaiser.dapar 2015-09-07
	 * @param 	int
	 * @param 	string
	 * @param 	datetime
	 * @param 	string
	 * @return	array
	 */
	public function savePlayerUpdateLog($player_id, $changes, $updatedBy) {
		$this->player_functions->savePlayerChanges([
			'playerId' => $player_id,
			'changes' => $changes,
			'createdOn' => date('Y-m-d H:i:s'),
			'operator' => $updatedBy,
		]);
	}
}
