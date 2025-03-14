<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aobangpay.php';
/**
 * aobangpay  奥邦
 *
 * * AOBANGPAY_QUICKPAY_PAYMENT_API, ID: 5054
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.aobang2pay.com/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aobangpay_quickpay extends Abstract_payment_api_aobangpay {

	public function getPlatformCode() {
		return AOBANGPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'aobangpay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['product_type'] = self::PRODUCT_TYPE_QUICKPAY;
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
