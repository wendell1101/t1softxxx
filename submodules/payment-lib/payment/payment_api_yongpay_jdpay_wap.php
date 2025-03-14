<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yongpay.php';
/**
 * YONGPAY
 *
 * YONGPAY_JDPAY_WAP_PAYMENT_API, ID: 807
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.spay888.net/load
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yongpay_jdpay_wap extends Abstract_payment_api_yongpay {

	public function getPlatformCode() {
		return YONGPAY_JDPAY_WAP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yongpay_jdpay_wap';
    }

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_channelCode'] = self::DEFAULTNANK_JDPAY_WAP;
        $params['isMobile'] = true;
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
