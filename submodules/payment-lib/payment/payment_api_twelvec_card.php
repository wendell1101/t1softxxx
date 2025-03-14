<?php
require_once dirname(__FILE__) . '/abstract_payment_api_twelvec.php';

/**
 *
 * * TWELVEC_CARD_PAYMENT_API', ID: 5808
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
class Payment_api_twelvec_card extends Abstract_payment_api_twelvec {

    public function getPlatformCode() {
        return TWELVEC_CARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'twelvec_card';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        // $params['type'] = $this->getSystemInfo('type','vt');
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'get_bank_code', 'type' => 'text', 'label_lang' => 'cashier.player.get_card_code', 'value' => '', 'hint' => $this->getSystemInfo('get_bank_code_hint'), 'attr_onkeyup' => $this->getSystemInfo('get_bank_code'), 'attr_maxlength' => $this->getSystemInfo('get_bank_code_maxlength')),
            array('name' => 'get_card_serial', 'type' => 'text', 'label_lang' => 'cashier.player.get_security_serial', 'value' => '', 'hint' => $this->getSystemInfo('get_card_serial_hint'), 'attr_onkeyup' => $this->getSystemInfo('get_card_serial'), 'attr_maxlength' => $this->getSystemInfo('get_card_serial_maxlength')),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }
}
