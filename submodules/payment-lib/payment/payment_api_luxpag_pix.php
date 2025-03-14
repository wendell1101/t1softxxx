<?php
require_once dirname(__FILE__) . '/abstract_payment_api_luxpag.php';
/**
 * luxpag
 *
 * * LUXPAG_PIX_PAYMENT_API, ID: 5931
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://developer.luxpag.com/cn/reference/checkout-redirect.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_luxpag_pix extends Abstract_payment_api_luxpag {

	public function getPlatformCode() {
		return LUXPAG_PIX_PAYMENT_API;
	}

	public function getPrefix() {
		return 'luxpag_pix';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['method'] = self::CHANNEL_PIX;
	}

	# Hide bank selection drop-down
    public function getPlayerInputInfo()
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
	}
}