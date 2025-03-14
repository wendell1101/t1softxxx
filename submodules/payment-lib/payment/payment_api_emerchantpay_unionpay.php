<?php
require_once dirname(__FILE__) . '/abstract_payment_api_emerchantpay.php';

/**
 * Emerchant Payment
 *
 * * EMERCHANT_UNIONPAY_PAYMENT_API, 5438
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Sha key
 *
 * Field Values:
 *
 * * Extra Info:
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_emerchantpay_unionpay extends Abstract_payment_api_emerchantpay {

	public function getPlatformCode() {
		return EMERCHANT_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'emerchantpay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		## Ref: section 8.2 WPF API
		## UnionPay of Emerchantpay. Ref: section 1.8.15 Online Banking
    	$params['transaction_types']['transaction_type']['name_attr'] = self::TRANS_TYPE_ONLINEBANK;
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

////END OF FILE//////////////////
