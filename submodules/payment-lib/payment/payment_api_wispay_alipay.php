<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wispay.php';

/**
 *
 * WISPAY
 *
 * * 'WISPAY_ALIPAY_PAYMENT_API', ID 5635
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
class Payment_api_wispay_alipay extends Abstract_payment_api_wispay {

	public function getPlatformCode() {
		return WISPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'wispay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_ALIPAY;
        $params['channel'] = self::CHANNEL_PC;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormURL($params);
	}

	public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
