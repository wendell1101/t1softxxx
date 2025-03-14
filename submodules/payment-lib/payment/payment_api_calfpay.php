<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianhe.php';
/**
 * CALFPAY 小牛支付
 *
 * * CALFPAY_PAYMENT_API, ID: 5860
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.99999923.com/index/unifiedorder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_calfpay extends Abstract_payment_api_tianhe {

	public function getPlatformCode() {
		return CALFPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'calfpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['pay_type'] = $this->getSystemInfo('pay_type');
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