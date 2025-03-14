<?php
require_once dirname(__FILE__) . '/abstract_payment_api_999pay.php';

/**
 * 汇天付 - 支付宝H5
 * 
 *
 * HUITIAN_ALIPAY_H5_PAYMENT_API, ID: 5390
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://gateway.huitianpay.com/Pay/KDBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_huitian_alipay_h5 extends Abstract_payment_api_999pay {

	public function getPlatformCode() {
		return HUITIAN_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'huitian_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
			$params['P_ChannelID'] = self::P_CHANNEL_ALIPAY;
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
