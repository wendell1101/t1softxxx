<?php
require_once dirname(__FILE__) . '/abstract_payment_api_luoyipay.php';
/**
 * LUOYIPAY 罗伊支付
 *
 * * LUOYIPAY_PAYMENT_API, ID: 5861
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://lyabc.xyz/luoyi/merchant/payment/order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_luoyipay extends Abstract_payment_api_luoyipay {

	public function getPlatformCode() {
		return LUOYIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'luoyipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channelId'] = self::PAY_METHODS_BANKCARD;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}