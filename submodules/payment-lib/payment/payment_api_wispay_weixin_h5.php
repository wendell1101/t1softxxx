<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wispay.php';

/**
 *
 * WISPAY
 *
 * * 'WISPAY_WEIXIN_H5_PAYMENT_API', ID 5638
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
class Payment_api_wispay_weixin_h5 extends Abstract_payment_api_wispay {

	public function getPlatformCode() {
		return WISPAY_WEIXIN_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'wispay_weixin_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_WEIXIN;
        $params['channel'] = self::CHANNEL_MOBILE;
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
