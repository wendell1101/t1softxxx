<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ppay.php';
/**
 * PPAY PPAY支付
 * 
 *
 * PPAY_ALIPAY_PAYMENT_API, ID: 5469
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://45.249.247.175/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ppay_alipay extends Abstract_payment_api_ppay {

	public function getPlatformCode() {
		return PPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ppay_alipay';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
            $params['paytype'] = self::PAYTYPE_ALIPAY;
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
