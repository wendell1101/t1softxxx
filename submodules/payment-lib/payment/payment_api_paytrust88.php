<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paytrust88.php';

Class payment_api_paytrust88 extends Abstract_payment_api_paytrust88 {
    
    public function getPrefix() {
        return 'paytrust88';
	}

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode() {
		return PAYTRUST88_PAYMENT_API;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
		return $this->processPaymentToken($params);
	}
}