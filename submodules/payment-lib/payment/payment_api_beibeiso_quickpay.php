<?php
require_once dirname(__FILE__) . '/abstract_payment_api_beibeiso.php';
/**
 * 新贝富支付 - 网银快捷
 * 
 *
 * BEIBEISO_QUICKPAY_PAYMENT_API, ID: 554
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.beibeiso.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_beibeiso_quickpay extends Abstract_payment_api_beibeiso {

	public function getPlatformCode() {
		return BEIBEISO_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'beibeiso_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
			$params['type'] = self::P_CHANNEL_QUICKPAY;
		
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
