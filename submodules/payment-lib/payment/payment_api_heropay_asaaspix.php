<?php
require_once dirname(__FILE__) . '/abstract_payment_api_heropay.php';

/**
 *
 * HEROPAY
 *
 * * HEROPAY_ASAASPIX_PAYMENT_API, ID: 6274
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.br.zm-pay.com/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_heropay_asaaspix extends Abstract_payment_api_heropay {

	public function getPlatformCode() {
		return HEROPAY_ASAASPIX_PAYMENT_API;
	}

	public function getPrefix() {
		return 'heropay_asaaspix';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['productId'] = $this->getSystemInfo('productId');
	}

	protected function processPaymentUrlForm($params) {

        return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
	public function convertAmountToCurrency($amount) {
        return number_format($amount * 100, 0, '.', '') ;
    }
}
