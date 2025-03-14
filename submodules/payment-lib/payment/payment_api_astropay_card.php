<?php
require_once dirname(__FILE__) . '/abstract_payment_api_astropay.php';

/**
 *
 * * ASTROPAY_CARD_PAYMENT_API', ID: 5292
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key

 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_astropay_card extends Abstract_payment_api_astropay {

    public function getPlatformCode() {
        return ASTROPAY_CARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'astropay_card';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'get_bank_num', 'type' => 'text', 'label_lang' => 'cashier.player.get_card_num', 'value' => '', 'hint' => $this->getSystemInfo('get_bank_num_hint'), 'attr_onkeyup' => $this->getSystemInfo('get_bank_num'), 'attr_maxlength' => $this->getSystemInfo('get_bank_num_maxlength')),
            array('name' => 'get_card_code', 'type' => 'text', 'label_lang' => 'cashier.player.get_security_code', 'value' => '', 'hint' => $this->getSystemInfo('get_card_code_hint'), 'attr_onkeyup' => $this->getSystemInfo('get_card_code'), 'attr_maxlength' => $this->getSystemInfo('get_card_code_maxlength')),
            array('name' => 'get_exp_date', 'type' => 'text', 'label_lang' => 'cashier.player.get_exp_date', 'value' => '', 'hint' => $this->getSystemInfo('get_exp_date_hint'), 'attr_onkeyup' => $this->getSystemInfo('get_exp_date'), 'attr_maxlength' => $this->getSystemInfo('get_exp_date_maxlength')),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }
}
