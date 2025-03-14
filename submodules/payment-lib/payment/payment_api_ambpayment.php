<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ambpayment.php';

/**
 * AMBPAYMENT
 *
 * * AMBPAYMENT_PAYMENT_API, ID: 5802
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://www.hipay8888.com/api/transaction
 * * Key: ## Access Token ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ambpayment extends Abstract_payment_api_ambpayment {

    public function getPlatformCode() {
        return AMBPAYMENT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'ambpayment';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['service_id'] = self::PAY_METHODS_ONLINE_BANK;
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['field_required_player_bank_list'])){
                $memberBank = explode('-',$extraInfo['field_required_player_bank_list']);
                $params['payer_account_no'] = $memberBank[1];
                $params['bank_name'] = $memberBank[0];
            }
        }
    }

    public function getBankType($direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        } else {
            return parent::getBankType($direct_pay_extra_info);
        }
    }

    # Hide bank selection drop-down
    # 加上前綴 " field_required_ ", 欄位為必填
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            array('name' => 'field_required_player_bank_list' ,'type' => 'player_bank_list', 'select_lang'=>'Please Select Bank' ,'label_lang' => 'cashier.player.bank_num' ,'list' => $this->getPlayerBank(), 'default_option_value' => $this->getSystemInfo('default_option_value')),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }

}