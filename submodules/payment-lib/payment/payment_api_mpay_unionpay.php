<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mpay.php';

/**
 *
 * * MPAY_UNIONPAY_PAYMENT_API,        ID: 666
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: juxin
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mpay_unionpay extends Abstract_payment_api_mpay {

	public function getPlatformCode() {
		return MPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mpay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['device'] = $this->utils->is_mobile() ? '2' : '1' ;
        $params['bank'] = self::PAYTYPE_UNIONPAY ;
	}	

	# Hide bank selection drop-down
	 public function getPlayerInputInfo() {
	 	return array(
	 		array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	 	);
	 }

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
