<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pay3721.php';

/**
 * PAY3721  恒久
 *
 * * 'PAY3721_WEIXIN_PAYMENT_API', ID 5287
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay3721.cn/pay/api/api.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pay3721_weixin extends Abstract_payment_api_pay3721 {

	public function getPlatformCode() {
		return PAY3721_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pay3721_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
            $params['paytype'] = self::PAYTYPE_WEIXIN;
            $params['subject'] = $this->getSystemInfo("qq_num");

	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
            return $this->processPaymentUrlFormQRCode($params);
	}
}
