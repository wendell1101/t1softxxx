<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gfpay.php';

/**
 * GFPAY
 *
 * * GFPAY_WEIXIN_H5_PAYMENT_API, ID: 5366
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://gfpay.co/mwpay/api.php?do=CreateOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gfpay_weixin_h5 extends Abstract_payment_api_gfpay {

	public function getPlatformCode() {
		return GFPAY_WEIXIN_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gfpay_weixin_h5';
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
