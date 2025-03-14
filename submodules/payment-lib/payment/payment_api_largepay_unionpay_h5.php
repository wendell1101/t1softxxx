<?php
require_once dirname(__FILE__) . '/abstract_payment_api_largepay.php';

/**
 * LARGEPAY
 *
 * * LARGEPAY_UNIONPAY_H5_PAYMENT_API, ID: 5534
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.hongzhong777.com/gateway/pay.jsp
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_largepay_unionpay_h5 extends Abstract_payment_api_largepay {

	public function getPlatformCode() {
		return LARGEPAY_UNIONPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'largepay_unionpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_mode'] = self::PAYMODE_H5;
		$params['bank_code'] = self::BANKCODE_UNIONPAY_WAP;
		$params['card_type'] = self::CARD_TYPE;
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
