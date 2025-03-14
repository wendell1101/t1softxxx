<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bofubaopay_v2.php';
/**
 * LBOWNPAY_ALIPAY-支付寶
 * 
 *
 * * LBOWNPAY_ALIPAY_PAYMENT_API, ID: 746
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:https://www.bofubaopay_v2api.com/
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lbownpay_alipay extends Abstract_payment_api_bofubaopay_v2 {

	public function getPlatformCode() {
		return LBOWNPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lbownpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
        $params['istype'] = self::P_CHANNEL_ALIPAY;	
        $params['price'] = $this->convertAmountToCurrency($params['price']);
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
