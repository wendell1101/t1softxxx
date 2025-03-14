<?php
require_once dirname(__FILE__) . '/abstract_payment_api_starspay.php';

/**
 *
 * starspay
 *
 *
 * * 'STARSPAY_PAYMENT_API', ID 5990
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.stars-pay.com/api/gateway/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_starspay extends Abstract_payment_api_starspay {

	public function getPlatformCode() {
		return STARSPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'starspay';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {

    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
