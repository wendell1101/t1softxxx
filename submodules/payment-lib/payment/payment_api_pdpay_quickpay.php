<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pdpay.php';

/**
 *
 * PDPAY
 *
 *
 * * 'PDPAY_QUICKPAY_PAYMENT_API', ID 5452
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.podusl.com/postin
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pdpay_quickpay extends Abstract_payment_api_pdpay {

	public function getPlatformCode() {
		return PDPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pdpay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['tradeType'] = self::TRADETYPE_QUICKPAY;
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
