<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aimapay.php';

/**
 *
 * AIMAPAY 爱码支付
 *
 *
 * * 'AIMAPAY_WEIXIN_PAYMENT_API', ID 5381
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
class Payment_api_aimapay_weixin extends Abstract_payment_api_aimapay {

	public function getPlatformCode() {
		return AIMAPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'aimapay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_WEIXIN;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
