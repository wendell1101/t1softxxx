<?php
require_once 'PlayerCenterBaseController.php';

/**
 * Provides deposit function
 *
 * @property Playerbankdetails $playerbankdetails
 * @property Operatorglobalsettings $operatorglobalsettings
 */
class Deposit extends PlayerCenterBaseController {
	public function __construct(){
		parent::__construct();

        $this->preloadCashierVars();
        $this->preloadDepositVars();
	}

    protected function preloadDepositVars(){
		$this->load->model(['banktype', 'payment_account', 'playerbankdetails', 'sale_order', 'promorules', 'financial_account_setting', 'player_model']);

		$playerId = $this->load->get_var('playerId');
        $payment_all_accounts = $this->payment_account->getAvailableDefaultCollectionAccount($this->load->get_var('playerId'));

        $payment_manual_accounts = ($payment_all_accounts[MANUAL_ONLINE_PAYMENT]['enabled']) ? $payment_all_accounts[MANUAL_ONLINE_PAYMENT]['list'] : [];
        if($payment_all_accounts[LOCAL_BANK_OFFLINE]['enabled']){
            foreach($payment_all_accounts[LOCAL_BANK_OFFLINE]['list'] as $payment_account){
                $payment_manual_accounts[] = $payment_account;
            }
        }
        $payment_auto_accounts = ($payment_all_accounts[AUTO_ONLINE_PAYMENT]['enabled']) ? $payment_all_accounts[AUTO_ONLINE_PAYMENT]['list'] : [];

        $payment_accounts = [];
        foreach($payment_manual_accounts as $payment_account){
            $payment_accounts[] = $payment_account;
        }
        foreach($payment_auto_accounts as $payment_account){
            $payment_accounts[] = $payment_account;
        }

        $enabled_change_withdrawal_password = $this->operatorglobalsettings->getSettingJson('enabled_change_withdrawal_password');
        $enabled_change_withdrawal_password = (empty($enabled_change_withdrawal_password)) ? ['disable'] : $enabled_change_withdrawal_password;

        $enabledWithdrawalPassword = in_array('enable', $enabled_change_withdrawal_password);

        $playerContactNumber = $this->player_model->getPlayerContactNumber($playerId);
        $isVerifiedPhone = $this->player_model->isVerifiedPhone($playerId);
        $checkPlayerContactNumberVerified = !empty($playerContactNumber) && ($isVerifiedPhone);

		$this->load->vars('payment_accounts', $payment_accounts);
		$this->load->vars('payment_manual_accounts', $payment_manual_accounts);
		$this->load->vars('payment_auto_accounts', $payment_auto_accounts);

        $this->load->vars('deposit_process_mode', $this->operatorglobalsettings->getSettingIntValue('deposit_process', DEPOSIT_PROCESS_MODE1));
		$this->load->vars('enabled_withdrawal_password', $enabledWithdrawalPassword);

        $player_bank_accounts = (array)$this->playerbankdetails->getAvailableDepositBankDetail($playerId);

		$this->load->vars('player_bank_accounts', $player_bank_accounts);
        $this->load->vars('sub_nav_active', 'deposit');
        $this->load->vars('checkPlayerContactNumberVerified', $checkPlayerContactNumberVerified);
        if ($this->utils->isEnabledFeature('always_auto_transfer_if_only_one_game')) {
            $enable_pop_up = $this->utils->getConfig('enable_pop_up_banner_function');
            $this->load->vars('enable_pop_up', $enable_pop_up);
            //     $enable_pop_up = $this->utils->getConfig('enable_pop_up_banner_when_player_login_desktop') && !empty($this->utils->getConfig('pop_up_banner_when_player_login_img_path'));
            //     $this->load->vars('enable_pop_up', $enable_pop_up);
        }

    }

	public function index(){

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

        $payment_accounts = $this->load->get_var('payment_accounts');

        if(empty($payment_accounts)){
            redirect('/player_center2/deposit/empty_payment_account');
            return;
        }

        if(!empty($this->utils->getConfig('enable_deposit_custom_view'))){

            $category_order = $this->utils->getConfig('enable_deposit_custom_view')['category_list'];
            $payment_manual_accounts = $this->load->get_var('payment_manual_accounts');
            $payment_auto_accounts = $this->load->get_var('payment_auto_accounts');

            $accounts = [
                SECOND_CATEGORY_ONLINE_BANK =>  array_shift($payment_auto_accounts)
            ];

            foreach ($category_order as $category_id) {
                if ($category_id != SECOND_CATEGORY_ONLINE_BANK) {
                    $accounts[$category_id] = array_filter($payment_manual_accounts, function($acc) use ($category_id) {
                        return isset($acc->second_category_flag) && $acc->second_category_flag == $category_id;
                    });
                }
            }

            foreach ($category_order as $category_id) {
                if (!empty($accounts[$category_id])) {
                    if ($category_id == SECOND_CATEGORY_ONLINE_BANK) { // auto
                        $default_account = $accounts[$category_id]->id;
                        return redirect('/player_center2/deposit/auto_payment/' . $default_account);
                    } else { // manual
                        $default_account = reset($accounts[$category_id])->id;
                        return redirect('/player_center2/deposit/deposit_custom_view/' . $default_account);
                    }
                }
            }

            redirect('/player_center2/deposit/empty_payment_account');
        }

        if(empty($this->load->get_var('payment_manual_accounts'))){
            if($this->utils->isEnabledFeature('enable_deposit_category_view')) {
                redirect('/player_center2/deposit/deposit_category');
            }
            else if($this->utils->is_mobile() && !$this->config->item('disable_old_mobile_deposit_category_page') ) {
                $this->loadOldMobileCategoryView();
            }
            else {
                $payment_auto_accounts = $this->load->get_var('payment_auto_accounts');
                $payment_account = (empty($payment_auto_accounts)) ? FALSE : array_shift($payment_auto_accounts);
                redirect('/player_center2/deposit/auto_payment/' . $payment_account->payment_account_id);
            }
        }else{
            if($this->utils->isEnabledFeature('enable_deposit_category_view')) {
                redirect('/player_center2/deposit/deposit_category');
            }
            else {
                if($this->utils->is_mobile() && !$this->config->item('disable_old_mobile_deposit_category_page') ) {
                    $this->loadOldMobileCategoryView();
                }
                else {
                    redirect('/player_center2/deposit/manual_payment');
                }
            }
        }
	}

    public function loadOldMobileCategoryView() {
        $this->loadTemplate();
        $this->template->append_function_title(lang('Deposit'));
        $this->template->add_js('/common/js/player_center/player-cashier.js');
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/cashier/deposit_form');
        $this->template->render();
    }

	public function empty_payment_account(){
        $playerId = $this->load->get_var('playerId');
        $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);
        if($this->utils->getConfig('display_player_turnover')){
            $this->load->model('total_player_game_hour');
            $month = idate("m");
            $year = idate("Y");
            // $data['total_turnover'] = $this->total_player_game_month->sumGameLogsByPlayer($playerId, $year, $month);
            $key = "player_query_total_turnover-{$year}-{$month}-{$playerId}";
            $result = $this->utils->getJsonFromCache($key);
            if(empty($result)){
                // $totalTurnover = $this->total_player_game_month->sumGameLogsByPlayer($playerId, $year, $month);
                $current_date = new DateTime();
                $start_month_day = $current_date->format('Y-m-01');
                $end_month_day = $current_date->format('Y-m-t');
                $results = $this->total_player_game_hour->sumGameLogsByPlayerPerGameType($playerId, $start_month_day, $end_month_day);
                $totalTurnover = 0;
                if(!empty($results)){
                    foreach ($results as $result) {
                        $totalTurnover += $result['total_betting_amount'];
                    }
                }
                $result = array(
                    'total_turnover'    => $totalTurnover
                );
                $ttl = 300;
                $this->utils->saveJsonToCache($key, $result, $ttl);
                $data['total_turnover'] = $totalTurnover;
            } else {
                $data['total_turnover'] = isset($result['total_turnover']) ? $result['total_turnover'] : 0;
            }
        }

        $this->loadTemplate();
        $this->template->add_js('/common/js/player_center/player-cashier.js');
        $this->template->add_js('/common/js/player_center/deposit.js');
        $this->template->add_js('/common/js/player_center/player-bank-account.js');
        $this->template->add_js('/common/js/plugins/province_city_select.js');
        $this->template->add_js('/resources/js/validator.js');
        $this->template->add_js('/resources/third_party/clipboard/clipboard.min.js');
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/cashier/empty_payment_account', $data);
        $this->template->render();
    }

    public function deposit_custom_view($payment_account_id = NULL, $payment_category_flag = NULL){

        $custom_view = $this->utils->getConfig('enable_deposit_custom_view');
        $clinet_name = isset($custom_view['clinet_name']) ? $custom_view['clinet_name'] : '';
        $category_list = isset($custom_view['category_list']) ? $custom_view['category_list'] : [];

        $data['payment_category_list'] = $category_list;
        $data['clinet_name'] = $clinet_name;
        $data['enable_custom_view'] = !empty($custom_view) && !empty($clinet_name);

        if(empty($this->load->get_var('payment_manual_accounts'))){
            redirect('/player_center2/deposit/');
        }

        if (empty($payment_account_id)) {
            redirect('/player_center2/deposit/');
        }

        $data['deposit_method'] = 'manual';
        $data['payment_account_id'] = $payment_account_id;

        $playerId = $this->load->get_var('playerId');

        if(!$this->checkAgencyCreditMode($playerId)){
            return;
        }

        // $secure_id = FALSE;
        // if($this->load->get_var('deposit_process_mode') === DEPOSIT_PROCESS_MODE2){
        //     $secure_id = $this->sale_order->generateSecureId();
        // }
        // $data['secure_id'] = $secure_id;

        $default_bank_details = $this->playerbankdetails->getDefaultBankDetail($playerId);
        if( count($default_bank_details['deposit']) > 0) {
            $data['default_bank_details'] = $default_bank_details['deposit'][0];
            unset($data['default_bank_details']['bankName']);
        }
        else {
            $data['default_bank_details'] = [];
        }
        $data['bank_details'] = $this->playerbankdetails->getBankDetails($playerId);

        $data['bankTypeList'] = $this->banktype->getAvailableBankTypeList('deposit');

        $data['force_setup_player_deposit_bank_if_empty'] = $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_deposit_bank_account');
        $data['force_setup_player_withdraw_bank_if_empty'] = 0;
        #only check usdt withdrawal account

        $enabled_ewallet_acc_ovo_dana_feature = $this->config->item('hide_financial_account_ewallet_account_number');
        if ($enabled_ewallet_acc_ovo_dana_feature) {
            $data['enabled_ewallet_acc_ovo_dana_feature'] = $enabled_ewallet_acc_ovo_dana_feature;
            $data['exist_ovo_deposit_account'] = !empty($this->playerbankdetails->getAccountDetailsByPlayerIdAndBankCode($playerId, 'deposit', 'OVO')) ? true : false;
            $data['exist_dana_deposit_account'] = !empty($this->playerbankdetails->getAccountDetailsByPlayerIdAndBankCode($playerId, 'deposit', 'DANA')) ? true : false;
        }

        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_withdraw_bank_account')) {

            $withdraw_bank_details = $this->playerbankdetails->getWithdrawBankDetail($playerId);
            if (empty($withdraw_bank_details)) {
                $data['force_setup_player_withdraw_bank_if_empty'] = 1;
            }
        }

        $use_self_pick_promotion = $this->utils->isEnabledFeature('use_self_pick_promotion');
        $data['system_feature_use_self_pick_promotion'] = $use_self_pick_promotion;

        if( $this->utils->isEnabledFeature('enable_manual_deposit_realname') || $this->utils->isEnabledFeature('enable_manual_deposit_input_depositor_name')){
            $this->load->model(['player_model']);
            $playerDetailObject =  $this->player_model->getPlayerDetailsById($playerId);
            $firstName = $playerDetailObject->firstName;
            if (strlen($firstName)<=0){
                $data['firstNameflg']=0;
            }else{
                $data['firstNameflg']=1;
            }
            $data['firstName']=$firstName;
        }

        $disable_preload = $this->utils->getConfig('disable_preload_available_promo_list');
        $apl = $this->promorules->getAvailPromoCmsList($playerId, $disable_preload);
        if($use_self_pick_promotion){
            $data['avail_promocms_list'] = (empty($apl)) ? [] : $apl;
        }else{
            $data['avail_promocms_list'] = [];
        }

        $last_manually_sale_order = $this->sale_order->getLastManuallyDeposit($playerId);
        $data['last_manually_sale_order'] = $last_manually_sale_order;

        $enable_manual_deposit_request_cool_down = json_decode($this->operatorglobalsettings->getSetting('manual_deposit_request_cool_down')->value,true);
        $enable_manual_deposit_request_cool_down = !empty($enable_manual_deposit_request_cool_down) ? $enable_manual_deposit_request_cool_down : Sale_order::ENABLE_MANUALLY_DEPOSIT_COOL_DOWN;
        $manual_deposit_request_cool_down_time = json_decode($this->operatorglobalsettings->getSetting('manual_deposit_request_cool_down_time')->value,true);
        $manual_deposit_request_cool_down_time = !empty($manual_deposit_request_cool_down_time) ? $manual_deposit_request_cool_down_time : Sale_order::DEFAULT_MANUALLY_DEPOSIT_COOL_DOWN_MINUTES;

        if($enable_manual_deposit_request_cool_down == Sale_order::ENABLE_MANUALLY_DEPOSIT_COOL_DOWN){
            $last_manually_unfinished_sale_order = $this->sale_order->getLastUnfinishedManuallyDeposit($playerId);
            $data['last_manually_unfinished_sale_order'] = $last_manually_unfinished_sale_order;
            $manually_deposit_cool_down_minutes = $this->getDepositRequesCoolDownTime($manual_deposit_request_cool_down_time);
            $data['manually_deposit_cool_down_minutes']=$manually_deposit_cool_down_minutes;
            if(!empty($last_manually_unfinished_sale_order) && isset($last_manually_unfinished_sale_order['created_at'])){
                $getTimeLeft = $this->utils->getMinuteBetweenTwoTime($last_manually_unfinished_sale_order['created_at'],$manually_deposit_cool_down_minutes);
                $data['getTimeLeft']=$getTimeLeft;
            }
            // $getTimeLeft = $this->utils->getMinuteBetweenTwoTime($last_manually_unfinished_sale_order['created_at'],$manually_deposit_cool_down_minutes);
            // $data['getTimeLeft']=$getTimeLeft;
            //check cold down time
            if($last_manually_unfinished_sale_order && !$this->utils->isTimeoutNow($last_manually_unfinished_sale_order['created_at'], $manually_deposit_cool_down_minutes) ){
                //not reach cool down time
                $data['in_cool_down_time'] = true;
            }else{
                $data['in_cool_down_time'] = false;
            }
        }

        $use_self_pick_subwallets = $this->utils->isEnabledFeature('use_self_pick_subwallets');
        $data['system_feature_use_self_pick_subwallets'] = $use_self_pick_subwallets;
        if($use_self_pick_subwallets){
            $data['pick_subwallets'] = $this->wallet_model->getSubwalletMap();
        }else{
            $data['pick_subwallets'] = [];
        }

        $data['show_tag_for_unavailable_deposit_accounts'] = (int) $this->system_feature->isEnabledFeature('show_tag_for_unavailable_deposit_accounts');
        $data['disable_account_transfer_when_balance_check_fails'] = (int) $this->system_feature->isEnabledFeature('disable_account_transfer_when_balance_check_fails');
        $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);

        $currentPaymentAccount=null;
        $data['payment_type_flag'] = Financial_account_setting::PAYMENT_TYPE_FLAG_BANK;

        $data['default_show_category_id'] = current($category_list);
        if(!empty($payment_account_id)){
            $currentPaymentAccount=$this->payment_account->getAvailableAccount($playerId, NULL, NULL, FALSE, $payment_account_id);

            if(empty($currentPaymentAccount)) {
                $this->utils->debug_log('==============manual_payment get empty currentPaymentAccount', $currentPaymentAccount);
                redirect('/player_center2/deposit/empty_payment_account');
                return;
            }

            $data['payment_manual_accounts'] = [$payment_account_id => $currentPaymentAccount];
            $data['second_category_flag'] = $currentPaymentAccount->second_category_flag;
            $data['preset_amount_buttons'] = $currentPaymentAccount->preset_amount_buttons;
            $data['default_show_category_id'] = (int)$currentPaymentAccount->second_category_flag;

            $banktype = $this->banktype->getBankTypeById($currentPaymentAccount->payment_type_id);
            $data['payment_type_flag'] = $banktype->payment_type_flag;
            $data['exist_crypto_account'] = false;
            if($this->utils->isCryptoCurrency($banktype)){
                $cryptocurrency = $this->utils->getCryptoCurrency($banktype);
                $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
                list($crypto, $rate) = $this->utils->convertCryptoCurrency(1, $cryptocurrency, $cryptocurrency, 'deposit');
                $custom_deposit_rate = $this->config->item('custom_deposit_rate') ? $this->config->item('custom_deposit_rate') : 1;
                $player_rate = number_format($rate * $custom_deposit_rate, 8, '.', '');
                $data['custCryptoUpdateTiming'] = $this->utils->getCustCryptoUpdateTiming($cryptocurrency);
                $data['custCryptoInputDecimalPlaceSetting'] = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency);
                 $data['custCryptoInputDecimalReciprocal'] = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
                $data['defaultCurrency'] = $defaultCurrency;
                $data['custFixRate'] = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
                $data['cryptocurrency'] = $cryptocurrency;
                $this->load->library('session');
                $this->session->set_userdata('cryptocurrency_rate', $rate);
                $data['cryptocurrency_rate'] = $rate;
                $data['currency_conversion_rate'] = $rate > 0 ? (1/$rate) : 0;
                $data['is_cryptocurrency'] = TRUE;
                $data['decimal_digit'] = $this->config->item('cryptocurrency_decimal_digit') ? $this->config->item('cryptocurrency_decimal_digit') : 8;
                if($this->utils->getConfig('disabel_deposit_bank')){
                    $payment_type = 'withdrawal';
                }else{
                    $payment_type = 'deposit';
                }
                $player_crypto_account = $this->playerbankdetails->getCryptoAccountByPlayerId($playerId, $payment_type, $cryptocurrency, 'payment');
                $data['player_crypto_account'] = $player_crypto_account;
                $this->utils->debug_log('player_crypto_account', $data['player_crypto_account'], $playerId, $payment_type, $cryptocurrency);
            }
            elseif($this->config->item('fix_currency_conversion_rate')){
                $data['fix_currency_conversion'] = true;
                $data['currency_conversion_rate'] = $this->config->item('fix_currency_conversion_rate');
                $data['decimal_digit'] = $this->config->item('fix_currency_decimal_digit') ? $this->config->item('fix_currency_decimal_digit') : 2;
                $data['base_currency']        = lang('currency.' . $this->config->item('fix_base_currency'));
                $data['target_currency']      = lang('currency.' . $this->config->item('fix_target_currency'));
                $data['base_currency_code']   = $this->config->item('fix_base_currency');
                $data['target_currency_code'] = $this->config->item('fix_target_currency');

                $searchArr  = ['{base_currency}', '{base_currency_code}', '{target_currency}', '{target_currency_code}', '{conversion_rate}', '{result}'];
                $replaceArr = [$data['base_currency'], $data['base_currency_code'], $data['target_currency'], $data['target_currency_code'], $data['currency_conversion_rate'], '<span class="currency_conversion_result"></span>'];

                $data['fix_rate_note'] = str_replace($searchArr, $replaceArr, lang('fix_rate_note'));
                $data['fix_rate_convert_result_note'] = str_replace($searchArr, $replaceArr, lang('convert_result_note'));
            }
            $data['isAlipay'] = $this->utils->isAlipay($banktype);
            $data['isUnionpay'] = $this->utils->isUnionpay($banktype);
            $data['isWechat'] = $this->utils->isWechat($banktype);
        }
        else {
            $data['isAlipay'] = false;
            $data['isUnionpay'] = false;
            $data['isWechat'] = false;
        }

        $data['enable_deposit_upload_documents']=$this->utils->isEnabledFeature('enable_deposit_upload_documents');
        $data['required_deposit_upload_file_1']=$this->utils->isEnabledFeature('required_deposit_upload_file_1');

        if(isset($banktype)){
            $field_show = $this->financial_account_setting->getFieldShowByPaymentAccountFlag($banktype->payment_type_flag);
        }
        else{
            $field_show = $this->financial_account_setting->getFieldShowByPaymentAccountFlag(Financial_account_setting::PAYMENT_TYPE_FLAG_BANK);
        }

        $data['hide_bank_branch_in_payment_account_detail_player_center'] = !in_array(Financial_account_setting::FIELD_BANK_BRANCH, $field_show);
        $data['hide_mobile_in_payment_account_detail_player_center'] = !in_array(Financial_account_setting::FIELD_PHONE, $field_show);
        $data['hide_deposit_selected_bank_and_text_for_ole777'] = $this->utils->isEnabledFeature('hide_deposit_selected_bank_and_text_for_ole777');
        $data['checkPlayerContactNumberVerified'] = $this->load->get_var('checkPlayerContactNumberVerified');

        $this->loadTemplate();
        $data['append_js_content'] = null;
        $data['append_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'exoclick', 'head');
        $append_js_to_specify_manual_payment = !empty($this->utils->getConfig('append_js_to_specify_manual_payment')) ? $this->utils->getConfig('append_js_to_specify_manual_payment') : [];
        if(!empty($data['append_js_filepath']['manual_payment']) && in_array($payment_account_id, $append_js_to_specify_manual_payment)){
            $data['append_js_content'] = $data['append_js_filepath']['manual_payment'];
            $this->template->add_js($data['append_js_content']);
        }

        $this->CI->load->library(['iovation_lib']);
        $data['is_iovation_enabled'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;
        if($data['is_iovation_enabled']){
            $this->template->add_function_js('/common/js/player_center/iovation.js');
            if($this->utils->getConfig('iovation')['use_first_party']){
                $this->template->add_js($this->utils->jsUrl($this->utils->getConfig('iovation')['first_party_js_config']));
            }else{
                $this->template->add_js($this->utils->jsUrl('config.js'));
            }
            $this->template->add_js($this->utils->jsUrl('iovation.js'));
        }

        $data['second_category_flags']  = $this->utils->insertEmptyToHeader($this->utils->getPaymentAccountSecondCategoryAllFlagsKV(), '', lang('select.empty.line'));

        $data['enabled_quick_add_account_button']=$this->utils->getConfig('enabled_quick_add_account_button');

        $this->template->add_js('/common/js/player_center/player-cashier.js');
        $this->template->add_js('/common/js/player_center/deposit.js');
        $this->template->add_js('/common/js/plugins/province_city_select.js');
        $this->template->add_js('/resources/js/validator.js');
        $this->template->add_js('/resources/third_party/clipboard/clipboard.min.js');
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate(FALSE) . '/cashier/deposit/'.$clinet_name.'/deposit' , $data);
        $this->template->render();
    }

    public function deposit_category() {
        $this->load->model('payment_account');
        $player_id = $this->authentication->getPlayerId();
        if($this->utils->getPlayerStatus($player_id)==5){
            redirect('/player_center/menu');
        }


        $bank_details = $this->playerbankdetails->getBankDetails($player_id);
        $disable_bank_code_where_interbank_transfer = $this->utils->getConfig('disable_bank_code_where_interbank_transfer');
        $payment_all_accounts = $this->payment_account->getAvailableDefaultCollectionAccount($player_id);
        $payment_manual_accounts = ($payment_all_accounts[MANUAL_ONLINE_PAYMENT]['enabled']) ? $payment_all_accounts[MANUAL_ONLINE_PAYMENT]['list'] : [];

        if($payment_all_accounts[LOCAL_BANK_OFFLINE]['enabled']){
            foreach($payment_all_accounts[LOCAL_BANK_OFFLINE]['list'] as $payment_account){
                $payment_manual_accounts[] = $payment_account;
            }
        }

        uasort($payment_manual_accounts, function($a, $b){
            if(is_array($this->config->item('second_category_flag_orders')) && !empty($this->config->item('second_category_flag_orders'))){
                $second_category_flag_orders_of_manual_accounts = $this->config->item('second_category_flag_orders');
                if($second_category_flag_orders_of_manual_accounts[$a->second_category_flag]['prioritize_level'] < $second_category_flag_orders_of_manual_accounts[$b->second_category_flag]['prioritize_level'] ){
                    return -1;
                }else if($second_category_flag_orders_of_manual_accounts[$a->second_category_flag]['prioritize_level'] == $second_category_flag_orders_of_manual_accounts[$b->second_category_flag]['prioritize_level']){
                    return ($a->payment_order < $b->payment_order) ? -1 : 1;
                }else{
                    return 1;
                }
            }else{
                return ($a->payment_order < $b->payment_order) ? -1 : 1;
            }
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

        $data['payment_auto_accounts'] = $payment_auto_accounts;
        $data['payment_manual_accounts'] = $payment_manual_accounts;

        if( $this->utils->isEnabledFeature('enable_manual_deposit_realname') || $this->utils->isEnabledFeature('enable_manual_deposit_input_depositor_name')){
            $this->load->model(['player_model']);
            $playerDetailObject =  $this->player_model->getPlayerDetailsById($player_id);
            $firstName = $playerDetailObject->firstName;
            if (strlen($firstName)<=0){
                $data['firstNameflg']=0;
            }else{
                $data['firstNameflg']=1;
            }
            $data['firstName']=$firstName;
        }

        $player = $this->load->get_var('player');
        $data['realname'] = Player::getPlayerFullName($player['firstName'], $player['lastName'], $player['language']);
        $data['checkPlayerContactNumberVerified'] = $this->load->get_var('checkPlayerContactNumberVerified');

        $deposit_auto_list =
        '<div class="panel no-gutter">'.
            '<div class="panel-title">'.
                ( $this->utils->getConfig('player_center_desktop_hide_deposit_type_titles') ? '' : lang('xpj.deposit.onlinepayment') ).
            '</div>'.
            '<div class="panel-body row">'.
                '<div class="col-md-2 mc-ul">'.
                    '<ul id="second_category_auto_list" class="main-menu-nav player-center-navigation">'.
                    '</ul>'.
                '</div>'.
                '<div class="col-md-9 tab-content mc-content">'.
                    '<div class="table-responsive">'.
                        '<table class="table-deposit-option table">'.
                            '<tbody id="accout_item_auto_list">'.
                            '</tbody>'.
                        '</table>'.
                    '</div>'.
                '</div>'.
            '</div>'.
        '</div>';

        $deposit_manual_list =
        '<div class="panel no-gutter">'.
            '<div class="panel-title">'.
                ( $this->utils->getConfig('player_center_desktop_hide_deposit_type_titles') ? '' : lang('xpj.deposit.companypayment') ).
            '</div>'.
            '<div class="panel-body row">'.
                '<div class="col-md-2 mc-ul">'.
                    '<ul id="second_category_manual_list" class="main-menu-nav player-center-navigation">'.
                    '</ul>'.
                '</div>'.
                '<div class="col-md-9 tab-content mc-content">'.
                    '<div class="table-responsive">'.
                        '<table class="table-deposit-option table">'.
                            '<tbody id="accout_item_manual_list">'.
                            '</tbody>'.
                        '</table>'.
                    '</div>'.
                '</div>'.
            '</div>'.
        '</div>';
        $data['deposit_auto_list'] = $deposit_auto_list;
        $data['deposit_manual_list'] = $deposit_manual_list;
        $data['force_setup_player_deposit_bank_if_empty'] = ($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_deposit_bank_account') && $this->config->item('enabled_require_deposit_bank_account_in_deposit_category') && empty($bank_details['deposit']))? 1 : 0;

        $this->loadTemplate();
        $data['append_js_content'] = null;
        $data['append_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'exoclick', 'head');
        if(!empty($data['append_js_filepath']['deposit_category'])){
            $data['append_js_content'] = $data['append_js_filepath']['deposit_category'];
            $this->template->add_js($data['append_js_content']);
        }

        $data['append_ole777id_js_content'] = null;
        $data['append_ole777id_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'gtag', 'head');
        if(!empty($data['append_ole777id_js_filepath']['deposit_category'])){
            $data['append_ole777id_js_content'] = $data['append_ole777id_js_filepath']['deposit_category'];
            $this->template->add_js($data['append_ole777id_js_content']);
        }

        $data['append_ole777thb_js_content'] = null;
        $data['append_ole777thb_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'gtm_deposit_category', 'head');
        if(!empty($data['append_ole777thb_js_filepath']['deposit_category'])){
            $data['append_ole777thb_js_content'] = $data['append_ole777thb_js_filepath']['deposit_category'];
            $this->template->add_js($data['append_ole777thb_js_content']);
        }

        if($this->utils->getConfig('display_player_turnover')){
            $this->load->model('total_player_game_hour');
            $month = idate("m");
            $year = idate("Y");
            // $data['total_turnover'] = $this->total_player_game_month->sumGameLogsByPlayer($player_id, $year, $month);
            $key = "player_query_total_turnover-{$year}-{$month}-{$player_id}";
            $result = $this->utils->getJsonFromCache($key);
            if(empty($result)){
                // $totalTurnover = $this->total_player_game_month->sumGameLogsByPlayer($player_id, $year, $month);
                $current_date = new DateTime();
                $start_month_day = $current_date->format('Y-m-01');
                $end_month_day = $current_date->format('Y-m-t');
                $results = $this->total_player_game_hour->sumGameLogsByPlayerPerGameType($playerId, $start_month_day, $end_month_day);
                $totalTurnover = 0;
                if(!empty($results)){
                    foreach ($results as $result) {
                        $totalTurnover += $result['total_betting_amount'];
                    }
                }
                $result = array(
                    'total_turnover'    => $totalTurnover
                );
                $ttl = 300;
                $this->utils->saveJsonToCache($key, $result, $ttl);
                $data['total_turnover'] = $totalTurnover;
            } else {
                $data['total_turnover'] = isset($result['total_turnover']) ? $result['total_turnover'] : 0;
            }
        }

        $this->template->append_function_title(lang('Deposit'));
        $this->template->add_js('/common/js/player_center/player-cashier.js');
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate(false) . '/cashier/deposit/manual/deposit_category', $data);
        $this->template->render();
    }

    public function manual_payment($payment_account_id = NULL){
        if(empty($this->load->get_var('payment_manual_accounts'))){
            redirect('/player_center2/deposit/');
        }

        $data['deposit_method'] = 'manual';
        $data['payment_account_id'] = $payment_account_id;

        $playerId = $this->load->get_var('playerId');

        if(!$this->checkAgencyCreditMode($playerId)){
            return;
        }

        $secure_id = FALSE;
        if($this->load->get_var('deposit_process_mode') === DEPOSIT_PROCESS_MODE2){
            $secure_id = $this->sale_order->generateSecureId();
        }
        $data['secure_id'] = $secure_id;

        $default_bank_details = $this->playerbankdetails->getDefaultBankDetail($playerId);
        if( count($default_bank_details['deposit']) > 0) {
            $data['default_bank_details'] = $default_bank_details['deposit'][0];
            unset($data['default_bank_details']['bankName']);
        }
        else {
            $data['default_bank_details'] = [];
        }
        $data['bank_details'] = $this->playerbankdetails->getBankDetails($playerId);

        $data['bankTypeList'] = $this->banktype->getAvailableBankTypeList('deposit');

        $data['force_setup_player_deposit_bank_if_empty'] = $this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_deposit_bank_account');
        $data['force_setup_player_withdraw_bank_if_empty'] = 0;
        #only check usdt withdrawal account

        $enabled_ewallet_acc_ovo_dana_feature = $this->config->item('hide_financial_account_ewallet_account_number');
        if ($enabled_ewallet_acc_ovo_dana_feature) {
            $data['enabled_ewallet_acc_ovo_dana_feature'] = $enabled_ewallet_acc_ovo_dana_feature;
            $data['exist_ovo_deposit_account'] = !empty($this->playerbankdetails->getAccountDetailsByPlayerIdAndBankCode($playerId, 'deposit', 'OVO')) ? true : false;
            $data['exist_dana_deposit_account'] = !empty($this->playerbankdetails->getAccountDetailsByPlayerIdAndBankCode($playerId, 'deposit', 'DANA')) ? true : false;
        }

        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_withdraw_bank_account')) {

            $withdraw_bank_details = $this->playerbankdetails->getWithdrawBankDetail($playerId);
            if (empty($withdraw_bank_details)) {
                $data['force_setup_player_withdraw_bank_if_empty'] = 1;
            }
        }


        $use_self_pick_promotion = $this->utils->isEnabledFeature('use_self_pick_promotion');
        $data['system_feature_use_self_pick_promotion'] = $use_self_pick_promotion;

        if( $this->utils->isEnabledFeature('enable_manual_deposit_realname') || $this->utils->isEnabledFeature('enable_manual_deposit_input_depositor_name')){
            $this->load->model(['player_model']);
            $playerDetailObject =  $this->player_model->getPlayerDetailsById($playerId);
            $firstName = $playerDetailObject->firstName;
            if (strlen($firstName)<=0){
                $data['firstNameflg']=0;
            }else{
                $data['firstNameflg']=1;
            }
            $data['firstName']=$firstName;
        }

        $disable_preload = $this->utils->getConfig('disable_preload_available_promo_list');
        $apl = $this->promorules->getAvailPromoCmsList($playerId, $disable_preload);
        if($use_self_pick_promotion){
            $data['avail_promocms_list'] = (empty($apl)) ? [] : $apl;
        }else{
            $data['avail_promocms_list'] = [];
        }

        $last_manually_sale_order = $this->sale_order->getLastManuallyDeposit($playerId);
        $data['last_manually_sale_order'] = $last_manually_sale_order;

        $enable_manual_deposit_request_cool_down = json_decode($this->operatorglobalsettings->getSetting('manual_deposit_request_cool_down')->value,true);
        $enable_manual_deposit_request_cool_down = !empty($enable_manual_deposit_request_cool_down) ? $enable_manual_deposit_request_cool_down : Sale_order::ENABLE_MANUALLY_DEPOSIT_COOL_DOWN;
        $manual_deposit_request_cool_down_time = json_decode($this->operatorglobalsettings->getSetting('manual_deposit_request_cool_down_time')->value,true);
        $manual_deposit_request_cool_down_time = !empty($manual_deposit_request_cool_down_time) ? $manual_deposit_request_cool_down_time : Sale_order::DEFAULT_MANUALLY_DEPOSIT_COOL_DOWN_MINUTES;

        if($enable_manual_deposit_request_cool_down == Sale_order::ENABLE_MANUALLY_DEPOSIT_COOL_DOWN){
            $last_manually_unfinished_sale_order = $this->sale_order->getLastUnfinishedManuallyDeposit($playerId);
            $data['last_manually_unfinished_sale_order'] = $last_manually_unfinished_sale_order;
            $manually_deposit_cool_down_minutes = $this->getDepositRequesCoolDownTime($manual_deposit_request_cool_down_time);
            $data['manually_deposit_cool_down_minutes']=$manually_deposit_cool_down_minutes;
            if(!empty($last_manually_unfinished_sale_order) && isset($last_manually_unfinished_sale_order['created_at'])){
                $getTimeLeft = $this->utils->getMinuteBetweenTwoTime($last_manually_unfinished_sale_order['created_at'],$manually_deposit_cool_down_minutes);
                $data['getTimeLeft']=$getTimeLeft;
            }
            // $getTimeLeft = $this->utils->getMinuteBetweenTwoTime($last_manually_unfinished_sale_order['created_at'],$manually_deposit_cool_down_minutes);
            // $data['getTimeLeft']=$getTimeLeft;
            //check cold down time
            if($last_manually_unfinished_sale_order && !$this->utils->isTimeoutNow($last_manually_unfinished_sale_order['created_at'], $manually_deposit_cool_down_minutes) ){
                //not reach cool down time
                $data['in_cool_down_time'] = true;
            }else{
                $data['in_cool_down_time'] = false;
            }
        }

        $use_self_pick_subwallets = $this->utils->isEnabledFeature('use_self_pick_subwallets');
        $data['system_feature_use_self_pick_subwallets'] = $use_self_pick_subwallets;
        if($use_self_pick_subwallets){
            $data['pick_subwallets'] = $this->wallet_model->getSubwalletMap();
        }else{
            $data['pick_subwallets'] = [];
        }

        $data['show_tag_for_unavailable_deposit_accounts'] = (int) $this->system_feature->isEnabledFeature('show_tag_for_unavailable_deposit_accounts');
        $data['disable_account_transfer_when_balance_check_fails'] = (int) $this->system_feature->isEnabledFeature('disable_account_transfer_when_balance_check_fails');
        $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);

        $currentPaymentAccount=null;
        $data['payment_type_flag'] = Financial_account_setting::PAYMENT_TYPE_FLAG_BANK;
        if(!empty($payment_account_id)){
            $currentPaymentAccount=$this->payment_account->getAvailableAccount($playerId, NULL, NULL, FALSE, $payment_account_id);

            if(empty($currentPaymentAccount)) {
                $this->utils->debug_log('==============manual_payment get empty currentPaymentAccount', $currentPaymentAccount);
                redirect('/player_center2/deposit/empty_payment_account');
                return;
            }

            $data['payment_manual_accounts'] = [$payment_account_id => $currentPaymentAccount];
            $data['second_category_flag'] = $currentPaymentAccount->second_category_flag;
            $data['preset_amount_buttons'] = $currentPaymentAccount->preset_amount_buttons;

            $banktype = $this->banktype->getBankTypeById($currentPaymentAccount->payment_type_id);
            $data['payment_type_flag'] = $banktype->payment_type_flag;
            $data['exist_crypto_account'] = false;
            if($this->utils->isCryptoCurrency($banktype)){
                $cryptocurrency = $this->utils->getCryptoCurrency($banktype);
                $defaultCurrency  = $this->utils->getCurrentCurrency()['currency_code'];
                list($crypto, $rate) = $this->utils->convertCryptoCurrency(1, $cryptocurrency, $cryptocurrency, 'deposit');
                $custom_deposit_rate = $this->config->item('custom_deposit_rate') ? $this->config->item('custom_deposit_rate') : 1;
                $player_rate = number_format($rate * $custom_deposit_rate, 8, '.', '');
                $data['custCryptoUpdateTiming'] = $this->utils->getCustCryptoUpdateTiming($cryptocurrency);
                $data['custCryptoInputDecimalPlaceSetting'] = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency);
                 $data['custCryptoInputDecimalReciprocal'] = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
                $data['defaultCurrency'] = $defaultCurrency;
                $data['custFixRate'] = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
                $data['cryptocurrency'] = $cryptocurrency;
                $this->load->library('session');
                $this->session->set_userdata('cryptocurrency_rate', $rate);
                $data['cryptocurrency_rate'] = $rate;
                $data['currency_conversion_rate'] = (1/$rate);
                $data['is_cryptocurrency'] = TRUE;
                $data['decimal_digit'] = $this->config->item('cryptocurrency_decimal_digit') ? $this->config->item('cryptocurrency_decimal_digit') : 8;
                if($this->utils->getConfig('disabel_deposit_bank')){
                    $payment_type = 'withdrawal';
                }else{
                    $payment_type = 'deposit';
                }
                $player_crypto_account = $this->playerbankdetails->getCryptoAccountByPlayerId($playerId, $payment_type, $cryptocurrency, 'payment');
                $data['player_crypto_account'] = $player_crypto_account;
                $this->utils->debug_log('player_crypto_account', $data['player_crypto_account'], $playerId, $payment_type, $cryptocurrency);
            }
            elseif($this->config->item('fix_currency_conversion_rate')){
                $data['fix_currency_conversion'] = true;
                $data['currency_conversion_rate'] = $this->config->item('fix_currency_conversion_rate');
                $data['decimal_digit'] = $this->config->item('fix_currency_decimal_digit') ? $this->config->item('fix_currency_decimal_digit') : 2;
                $data['base_currency']        = lang('currency.' . $this->config->item('fix_base_currency'));
                $data['target_currency']      = lang('currency.' . $this->config->item('fix_target_currency'));
                $data['base_currency_code']   = $this->config->item('fix_base_currency');
                $data['target_currency_code'] = $this->config->item('fix_target_currency');

                $searchArr  = ['{base_currency}', '{base_currency_code}', '{target_currency}', '{target_currency_code}', '{conversion_rate}', '{result}'];
                $replaceArr = [$data['base_currency'], $data['base_currency_code'], $data['target_currency'], $data['target_currency_code'], $data['currency_conversion_rate'], '<span class="currency_conversion_result"></span>'];

                $data['fix_rate_note'] = str_replace($searchArr, $replaceArr, lang('fix_rate_note'));
                $data['fix_rate_convert_result_note'] = str_replace($searchArr, $replaceArr, lang('convert_result_note'));
            }
            $data['isAlipay'] = $this->utils->isAlipay($banktype);
            $data['isUnionpay'] = $this->utils->isUnionpay($banktype);
            $data['isWechat'] = $this->utils->isWechat($banktype);
        }
        else {
            $data['isAlipay'] = false;
            $data['isUnionpay'] = false;
            $data['isWechat'] = false;
        }

        $data['enable_deposit_upload_documents']=$this->utils->isEnabledFeature('enable_deposit_upload_documents');
        $data['required_deposit_upload_file_1']=$this->utils->isEnabledFeature('required_deposit_upload_file_1');

        if(isset($banktype)){
            $field_show = $this->financial_account_setting->getFieldShowByPaymentAccountFlag($banktype->payment_type_flag);
        }
        else{
            $field_show = $this->financial_account_setting->getFieldShowByPaymentAccountFlag(Financial_account_setting::PAYMENT_TYPE_FLAG_BANK);
        }

        $data['hide_bank_branch_in_payment_account_detail_player_center'] = !in_array(Financial_account_setting::FIELD_BANK_BRANCH, $field_show);
        $data['hide_mobile_in_payment_account_detail_player_center'] = !in_array(Financial_account_setting::FIELD_PHONE, $field_show);
        $data['hide_deposit_selected_bank_and_text_for_ole777'] = $this->utils->isEnabledFeature('hide_deposit_selected_bank_and_text_for_ole777');
        $data['checkPlayerContactNumberVerified'] = $this->load->get_var('checkPlayerContactNumberVerified');

		$this->loadTemplate();
        $data['append_js_content'] = null;
        $data['append_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'exoclick', 'head');
        $append_js_to_specify_manual_payment = !empty($this->utils->getConfig('append_js_to_specify_manual_payment')) ? $this->utils->getConfig('append_js_to_specify_manual_payment') : [];
        if(!empty($data['append_js_filepath']['manual_payment']) && in_array($payment_account_id, $append_js_to_specify_manual_payment)){
            $data['append_js_content'] = $data['append_js_filepath']['manual_payment'];
            $this->template->add_js($data['append_js_content']);
        }

        $data['append_ole777thb_js_content'] = null;
        $data['append_ole777thb_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'gtm_payment', 'head');
        if(!empty($data['append_ole777thb_js_filepath']['payment'])){
            $data['append_ole777thb_js_content'] = $data['append_ole777thb_js_filepath']['payment'];
            $this->template->add_js($data['append_ole777thb_js_content']);
        }

        $this->CI->load->library(['iovation_lib']);
        $data['is_iovation_enabled'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;
        if($data['is_iovation_enabled']){
            $this->template->add_function_js('/common/js/player_center/iovation.js');
            if($this->utils->getConfig('iovation')['use_first_party']){
                $this->template->add_js($this->utils->jsUrl($this->utils->getConfig('iovation')['first_party_js_config']));
            }else{
                $this->template->add_js($this->utils->jsUrl('config.js'));
            }
            $this->template->add_js($this->utils->jsUrl('iovation.js'));
        }

        $this->template->add_js('/common/js/player_center/player-cashier.js');
		$this->template->add_js('/common/js/player_center/deposit.js');
		$this->template->add_js('/common/js/plugins/province_city_select.js');
		$this->template->add_js('/resources/js/validator.js');
		$this->template->add_js('/resources/third_party/clipboard/clipboard.min.js');
		$this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate(FALSE) . '/cashier/deposit', $data);
		$this->template->render();
    }

    public function auto_payment($payment_account_id = NULL){
        $playerId = $this->load->get_var('playerId');

        $payment_auto_accounts = $this->load->get_var('payment_auto_accounts');
        if(empty($payment_auto_accounts)){
            redirect('/player_center2/deposit/');
        }

        if(!$this->checkAgencyCreditMode($playerId)){
            //TODO show permission error
            $this->returnBadRequest();
            return;
        }

        $payment_account = FALSE;
        foreach($payment_auto_accounts as $payment_account_entry){
            if($payment_account_entry->payment_account_id == $payment_account_id){
                $payment_account = $payment_account_entry;
            }
        }

        $data = [];
        $minDeposit = 0;
        $maxDeposit = 0;
        if(empty($payment_account)){
            $this->returnBadRequest();
            redirect($this->utils->getPlayerDepositUrl());
            return;
        }

        /* @var $api Abstract_payment_api */
        $api = $this->utils->loadExternalSystemLibObject($payment_account->external_system_id);
        if(empty($api)) {
            $this->returnBadRequest();
            return;
        }

        if(!$api->isAvailable()) {
            $this->returnBadRequest();
            return;
        }

        $auto_deposit_cool_down_minutes = $api->getNextOrderCooldownTime();
        if($auto_deposit_cool_down_minutes != 0){
            $data['auto_deposit_cool_down_minutes'] = $auto_deposit_cool_down_minutes;
            //check cold down time
            $lastOrder = $this->sale_order->getLastSalesOrderByPlayerId($playerId);
            $this->utils->debug_log('lastOrder', $lastOrder, 'auto_deposit_cool_down_minutes', $auto_deposit_cool_down_minutes);
            if($lastOrder && !$this->utils->isTimeoutNow($lastOrder->created_at, $auto_deposit_cool_down_minutes) ){
                //not reach cool down time
                $data['in_cool_down_time'] = true;
            }else{
                $data['in_cool_down_time'] = false;
            }
        }

        $data['exists_payment_account'] = TRUE;
        $data['external_system_api'] = $api;
        $data['extra_info'] = $api->getAllSystemInfo();
        $api->initPlayerPaymentInfo($playerId);

        $data['playerInputInfo'] = $api->getPlayerInputInfo();
        $data['disable_form'] = !!$api->getSystemInfo('disable_form');
        $data['disable_form_msg'] = $api->getSystemInfo('disable_form_msg');

        $data['minDeposit'] = $minDeposit = $payment_account->vip_rule_min_deposit_trans;
        $data['maxDeposit'] = $maxDeposit = $payment_account->vip_rule_max_deposit_trans;
        $data['preset_amount_buttons'] = $payment_account->preset_amount_buttons;
        $data['second_category_flag'] = $payment_account->second_category_flag;

        $api_special_limit_rule = $api->getSystemInfo('special_limit_rule');
        $custom_auto_deposit_amount_limit_rule = $this->CI->config->item('custom_auto_deposit_amount_limit_rule');
        if(!is_array($api_special_limit_rule)){
            $api_special_limit_rule = array($api_special_limit_rule);
        }
        if(!is_array($custom_auto_deposit_amount_limit_rule)){
            $custom_auto_deposit_amount_limit_rule = array($custom_auto_deposit_amount_limit_rule);
        }
        $data['special_limit_rules'] = array_merge($api_special_limit_rule, $custom_auto_deposit_amount_limit_rule);


        if ($this->utils->isEnabledFeature('use_self_pick_subwallets')) {
            $data['subwallets'] = $this->wallet_model->getSubwalletMap();
        }

        if ($this->utils->isEnabledFeature('use_self_pick_group')) {
            $vipsettings = $this->group_level->getAllCanJoinIn();
            if (!empty($vipsettings)) {
                foreach ($vipsettings as $vipsetting) {
                    if (!isset($data['vipsettings'][$vipsetting['vipSettingId']])) {
                        $data['vipsettings'][$vipsetting['vipSettingId']]['name'] = lang($vipsetting['groupName']);
                        $data['vipsettings'][$vipsetting['vipSettingId']]['description'] = $vipsetting['groupDescription'];
                    }
                    $data['vipsettings'][$vipsetting['vipSettingId']]['list'][] = $vipsetting;
                }
            }
        }

        $use_self_pick_promotion = $this->utils->isEnabledFeature('use_self_pick_promotion');
        $data['system_feature_use_self_pick_promotion'] = $use_self_pick_promotion;

        $disable_preload = $this->utils->getConfig('disable_preload_available_promo_list');
        $apl = $this->promorules->getAvailPromoCmsList($playerId, $disable_preload);
        if($use_self_pick_promotion){
            $data['avail_promocms_list'] = (empty($apl)) ? [] : $apl;
        }else{
            $data['avail_promocms_list'] = [];
        }

        $data['show_tag_for_unavailable_deposit_accounts'] = (int) $this->system_feature->isEnabledFeature('show_tag_for_unavailable_deposit_accounts');
        $data['disable_account_transfer_when_balance_check_fails'] = (int) $this->system_feature->isEnabledFeature('disable_account_transfer_when_balance_check_fails');

        $data['deposit_method'] = 'auto';
        $data['payment_account_id'] = $payment_account_id;
        $data['payment_account'] = $payment_account;
        $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);

        $data['big_wallet'] = $this->wallet_model->getOrderBigWallet($playerId);
        $data['pendingBalance'] = (object) ['frozen' => $data['big_wallet']['main']['frozen']];
        $data['totalBalance'] = $data['big_wallet']['total'];
        $subwallets = $data['big_wallet']['sub'];
        $data['subwallets'] = $subwallets;
        if ($this->utils->isEnabledFeature('use_self_pick_subwallets')) {
            $data['pick_subwallets'] = $this->wallet_model->getSubwalletMap();
        }else{
            $data['pick_subwallets'] = [];
        }

        if ($this->utils->isEnabledFeature('use_self_pick_group')) {
            $vipsettings = $this->group_level->getAllCanJoinIn();
            if (!empty($vipsettings)) {
                foreach ($vipsettings as $vipsetting) {
                    if (!isset($data['vipsettings'][$vipsetting['vipSettingId']])) {
                        $data['vipsettings'][$vipsetting['vipSettingId']]['name'] = $vipsetting['groupName'];
                        $data['vipsettings'][$vipsetting['vipSettingId']]['description'] = $vipsetting['groupDescription'];
                    }
                    $data['vipsettings'][$vipsetting['vipSettingId']]['list'][] = $vipsetting;
                }
            }
        }
        $data['hide_deposit_selected_bank_and_text_for_ole777']=$this->utils->isEnabledFeature('hide_deposit_selected_bank_and_text_for_ole777');
        $data['payment_type_flag'] = Financial_account_setting::PAYMENT_TYPE_FLAG_API;

        $data['force_setup_player_withdraw_bank_if_empty'] = 0;
        if ($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_require_withdraw_bank_account')) {
            $withdraw_bank_details = $this->playerbankdetails->getWithdrawBankDetail($playerId);
            if (empty($withdraw_bank_details)) {
                $data['force_setup_player_withdraw_bank_if_empty'] = 1;
            }
        }

        // OGP-23071
        $data['fixed_exchange_rate'] = floatval($api->getSystemInfo('fixed_exchange_rate'));

        #OGP-20364
        $auto_payment_crypto_currency_api = $this->config->item('auto_payment_crypto_currency_api');
        $data['auto_payment_crypto_currency_api'] = 0;
        if (is_array($auto_payment_crypto_currency_api) && in_array($payment_account->external_system_id, $auto_payment_crypto_currency_api)) {
            $data['auto_payment_crypto_currency_api'] = 1;
        }

        $this->utils->debug_log('=========================================extra_info', $data['extra_info']);

		$this->loadTemplate();

        $this->CI->load->library(['iovation_lib']);
        $data['is_iovation_enabled'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;
        if($data['is_iovation_enabled']){
            $this->template->add_function_js('/common/js/player_center/iovation.js');
            if($this->utils->getConfig('iovation')['use_first_party']){
                $this->template->add_js($this->utils->jsUrl($this->utils->getConfig('iovation')['first_party_js_config']));
            }else{
                $this->template->add_js($this->utils->jsUrl('config.js'));
            }
            $this->template->add_js($this->utils->jsUrl('iovation.js'));
        }
        if($payment_account->external_system_id == 6136){
            $custom_deposit_static = $this->utils->getConfig('use_custom_deposit_static');
            if ($custom_deposit_static){
                $static_file = VIEWPATH . '/resources/includes/custom_deposit_static/'.$custom_deposit_static.'/static_'.$payment_account->external_system_id.'.php';
                $data['deposit_method'] = 'auto_static_html';
                $data['currentLang'] = $this->language_function->getCurrentLanguageName();
                $data['account_icon_url'] = $payment_account->account_icon_url;
                $this->utils->debug_log('==============static html file', $static_file);
                if(file_exists($static_file)) {
                    $data['static_html'] = $static_file;
                }else{
                    $data['static_html'] = '';
                }
            }else{
                $this->returnBadRequest();
                return;
            }
        }

        $data['append_ole777thb_js_content'] = null;
        $data['append_ole777thb_js_filepath'] = $this->utils->getTrackingScriptWithDoamin('player', 'gtm_payment', 'head');
        if(!empty($data['append_ole777thb_js_filepath']['payment'])){
            $data['append_ole777thb_js_content'] = $data['append_ole777thb_js_filepath']['payment'];
            $this->template->add_js($data['append_ole777thb_js_content']);
        }

        $this->template->add_js('/common/js/player_center/player-cashier.js');
        $this->template->add_js('/common/js/player_center/deposit.js');
        $this->template->add_js('/resources/js/validator.js');
        $this->template->add_js('/resources/third_party/clipboard/clipboard.min.js');

        $custom_view = $this->utils->getConfig('enable_deposit_custom_view');
        if (!empty($custom_view)) {

            $payment_accounts = $this->load->get_var('payment_accounts');
            $clinet_name = isset($custom_view['clinet_name']) ? $custom_view['clinet_name'] : '';
            $category_list = isset($custom_view['category_list']) ? $custom_view['category_list'] : [];

            $data['payment_accounts'] = $payment_accounts;
            $data['payment_category_list'] = $category_list;
            $data['clinet_name'] = $clinet_name;
            $data['default_show_category_id'] = (int)$payment_account->second_category_flag;
            $data['second_category_flags']  = $this->utils->insertEmptyToHeader($this->utils->getPaymentAccountSecondCategoryAllFlagsKV(), '', lang('select.empty.line'));
            $data['enable_custom_view'] = !empty($custom_view) && !empty($clinet_name);

            $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/cashier/deposit/'.$clinet_name.'/deposit' , $data);
        }else{
            $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/cashier/deposit', $data);
        }
		$this->template->render();
    }

    function deposit_instruction(){
        $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/cashier/deposit_instruction');
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
}
