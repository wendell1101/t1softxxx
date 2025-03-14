<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypays.php';

/** 
 *
 * easypays
 * 
 * 
 * * 'EASYPAYS_ALIPAY_H5_PAYMENT_API', ID 5226
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.easypays.vip/get_qrcode_link
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_easypays_alipay_h5 extends Abstract_payment_api_easypays {

	public function getPlatformCode() {
		return EASYPAYS_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'easypays_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channel'] = $this->getSystemInfo("channel_type","alipaybag");
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormRedirectBag($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
