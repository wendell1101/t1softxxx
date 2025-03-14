<?php

/**
 * Class Widget_Lang
 *
 * @author Elvis Chen
 */
class Widget_Lang extends MY_Widget {
    public function initialize($options = []){

        $lang = [];
        $lang['Upgrade Condition'] = lang('Upgrade Condition');
        $lang['text.loading'] = lang('text.loading');
        $lang['lang.close'] = lang('lang.close');
        $lang['lang.confirm'] = lang('Confirm');
        $lang['lang.settings'] = lang('lang.settings');
        $lang['alert-success'] = lang('alert-success');
        $lang['alert-info'] = lang('alert-info');
        $lang['alert-warning'] = lang('alert-warning');
        $lang['alert-danger'] = lang('alert-danger');
        $lang['alert-notice'] = lang('alert-notice');
        $lang['Please update account name to add bank account'] = lang('Please update account name to add bank account');
        $lang['Please update phone number to add bank account'] = lang('Please update phone number to add bank account');
        $lang['Please update CPF number to add bank account'] = lang('Please update CPF number to add bank account');
        $lang['Please contact administrator to update your CPF Number'] = lang('Please contact administrator to update your CPF Number');
        $lang['pay.reg'] = lang('pay.reg');
        $lang['cashier.deposit.force_setup_player_deposit_bank_hint'] = lang('cashier.deposit.force_setup_player_deposit_bank_hint');
        $lang['cashier.deposit.force_setup_player_withdraw_bank_hint'] = lang('cashier.deposit.force_setup_player_withdraw_bank_hint');
        $lang['cashier.withdrawal.player_center_withdrawal_bank_messagebox_title_info'] = lang('cashier.withdrawal.player_center_withdrawal_bank_messagebox_title_info');
        $lang['cashier.withdrawal.force_setup_player_withdrawal_bank_hint'] = lang('cashier.withdrawal.force_setup_player_withdrawal_bank_hint');
        $lang['Please provide First Name and Last Name upon binding a bank'] = lang('Please provide First Name and Last Name upon binding a bank');
        $lang['internet_banking'] = lang('internet_banking');
        $lang['over_the_counter_deposit'] = lang('over_the_counter_deposit');
        $lang['atm_transfer'] = lang('atm_transfer');
        $lang['mobile_banking'] = lang('mobile_banking');
        $lang['cash_deposit_machine'] = lang('cash_deposit_machine');
        $lang['This is a required field'] = lang('This is a required field');
        $lang['Before adding a bank account, please set your'] = lang('Before adding a bank account, please set your');
        $lang['Real Name'] = lang('Real Name');
        $lang['Birthday'] = lang('Birthday');
        $lang['Changing Currency'] = lang('Changing Currency');
        $lang['Change Currency Failed'] = lang('Change Currency Failed');
        $lang['Please Enter Crypto Amount'] = sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto"));
        $lang['Please select a bank account with virtual currency type'] = lang('Please select a bank account with virtual currency type');
        $lang['Please bind a crypto wallet before using this method'] = lang('Please bind a crypto wallet before using this method');
        $lang['CN Yuan'] = lang('CN Yuan');
        $lang['custom_promo_sucess_msg.ole777ind.1'] = lang('custom_promo_sucess_msg.ole777ind.1');
        $lang['custom_promo_sucess_msg.ole777ind.2'] = lang('custom_promo_sucess_msg.ole777ind.2');
        $lang['promorule.prompt.success'] = lang('promorule.prompt.success');
        $lang['promo_custom.birthdate_not_set_yet'] = lang('promo_custom.birthdate_not_set_yet');
        $lang['reg.13'] = lang('reg.13');
        $lang['please_select'] = lang('please_select');
        $lang['financial_account.CPF_number'] = lang('financial_account.CPF_number');
        $lang['financial_account.phone'] = lang('financial_account.phone');
        $lang['lang.email'] = lang('lang.email');
        $lang['promo_countdown.end'] = lang('promo_countdown.end');
        $lang['promo_countdown.Day'] = lang('promo_countdown.Day');
        $lang['promo_countdown.Hour'] = lang('promo_countdown.Hour');
        $lang['promo_countdown.Min'] = lang('promo_countdown.Min');
        $lang['promo_countdown.Sec'] = lang('promo_countdown.Sec');
        //Please Input Withdrawal Amount in
        $lang['Please Input Withdrawal Amount MXN'] = lang('input_withdrawal_amount.mxn');
        $lang['Please Input Withdrawal Amount CLP'] = lang('input_withdrawal_amount.clp');
        $lang['Please Input Withdrawal Amount PEN'] = lang('input_withdrawal_amount.pen');
        $this->_data['lang'] = $lang;
    }
}