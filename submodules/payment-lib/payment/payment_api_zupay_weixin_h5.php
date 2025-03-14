<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zupay.php';
/**
 * ZUPAY 微信H5
 * 
 *
 * ZUPAY_WEIXIN_H5_PAYMENT_API, ID: 766
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.zupay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zupay_weixin_h5 extends Abstract_payment_api_zupay {

	public function getPlatformCode() {
		return ZUPAY_WEIXIN_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zupay_weixin_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

            $params['pay_bankcode'] = self::DEFAULTNANK_WEIXIN_WAP;
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
