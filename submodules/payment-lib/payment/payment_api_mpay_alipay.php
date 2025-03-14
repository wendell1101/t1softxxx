<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mpay.php';

/**
 *
 * * MPAY_ALIPAY_PAYMENT_API, ID: 649
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: mpay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mpay_alipay extends Abstract_payment_api_mpay {

	public function getPlatformCode() {
		return MPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['device'] = $this->utils->is_mobile() ? '2' : '1' ;
        $params['bank'] = $this->utils->is_mobile() ? self::PAYTYPE_ALIPAY_H5 : self::PAYTYPE_ALIPAY ;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
	}
}
