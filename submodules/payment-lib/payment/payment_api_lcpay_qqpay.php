<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lcpay.php';

/**
 * LCPAY 乐橙支付 - QQ
 * 
 *
 * LCPAY_QQPAY_PAYMENT_API, ID: 398
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://zfjk.lchuyu.com/gateway/pb/service/order/createWxCode
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lcpay_qqpay extends Abstract_payment_api_lcpay {

	public function getPlatformCode() {
		return LCPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lcpay_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['payChannel'] = self::PAYCHANNEL_QQPAY;
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
