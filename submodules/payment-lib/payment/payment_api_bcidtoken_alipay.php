<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypays.php';

/** 
 *
 * bcidtoken
 * 
 * 
 * * 'BCIDTOKEN_ALIPAY_PAYMENT_API', ID 5280
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.bcidtoken.com/get_qrcode_link
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bcidtoken_alipay extends Abstract_payment_api_easypays {

	public function getPlatformCode() {
		return BCIDTOKEN_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bcidtoken_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = "alipay";
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
