<?php
require_once dirname(__FILE__) . '/abstract_payment_api_abbpay.php';

/** 
 *
 * ABB
 * 
 * 
 * * 'ABBPAY_WEIXIN_PAYMENT_API', ID 5114
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.mobai79.cn
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_abbpay_weixin extends Abstract_payment_api_abbpay {

	public function getPlatformCode() {
		return ABBPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'abbpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['payway'] = self::PAYWAY_WEIXIN;
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormPost($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
