<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wispay.php';

/**
 *
 * WISPAY
 *
 * * 'WISPAY_QUICKPAY_PAYMENT_API', ID 5335
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_wispay_quickpay extends Abstract_payment_api_wispay {

	public function getPlatformCode() {
		return WISPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'wispay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_QUICKPAY;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {

        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
