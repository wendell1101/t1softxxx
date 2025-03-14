<?php
require_once dirname(__FILE__) . '/abstract_payment_api_beibeiso.php';
/**
 * 新贝富支付 - QQ
 * 
 *
 * BEIBEISO_QQPAY_PAYMENT_API, ID: 552
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.beibeiso.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_beibeiso_qqpay extends Abstract_payment_api_beibeiso {

	public function getPlatformCode() {
		return BEIBEISO_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'beibeiso_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
			if($this->CI->utils->is_mobile()) {
				$params['type'] = self::P_CHANNEL_QQPAY_H5;
			}
			else {
				$params['type'] = self::P_CHANNEL_QQPAY;
			}	
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
