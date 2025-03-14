<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bohaipay.php';
/**
 * 渤海支付 - QQ
 * 
 *
 * BOHAIPAY_QQPAY_PAYMENT_API, ID: 535
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.bohaipay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bohaipay_qqpay extends Abstract_payment_api_bohaipay {

	public function getPlatformCode() {
		return BOHAIPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bohaipay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
			$params['type'] = self::P_CHANNEL_QQPAY;
		
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
