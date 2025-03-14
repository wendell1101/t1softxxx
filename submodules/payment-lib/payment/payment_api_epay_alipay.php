<?php
require_once dirname(__FILE__) . '/abstract_payment_api_epay.php';

/**
 *
 * EPAY_ALIPAY
 *
 * * EPAY_ALIPAY_PAYMENT_API, ID: 5953
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.epay666.com/api/deposit
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_epay_alipay extends Abstract_payment_api_epay {

	public function getPlatformCode() {
		return EPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'epay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['paymentMethod'] = self::CHANNEL_ALIPAY;
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