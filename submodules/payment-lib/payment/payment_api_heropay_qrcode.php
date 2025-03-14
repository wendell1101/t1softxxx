<?php
require_once dirname(__FILE__) . '/abstract_payment_api_heropay.php';

/**
 *
 * HEROPAY
 *
 * * HEROPAY_QRCODE_PAYMENT_API, ID: 5806
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.251.11.242:3020/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_heropay_qrcode extends Abstract_payment_api_heropay {

	public function getPlatformCode() {
		return HEROPAY_QRCODE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'heropay_qrcode';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = $this->getSystemInfo('productId') ? $this->getSystemInfo('productId') : self::PAY_TYPE_QRCODE;
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
