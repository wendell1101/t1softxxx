<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dgajbupay.php';

/**
 * dgajbupay_alipay
 *
 *
 * DGAJBUPAY_ALIPAY_PAYMENT_API, ID: 6095
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.dgajbu.com/pay_index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dgajbupay_alipay extends Abstract_payment_api_dgajbupay {

	public function getPlatformCode() {
		return DGAJBUPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dgajbupay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_bankcode'] = self::PAYTYPE_ALIPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
