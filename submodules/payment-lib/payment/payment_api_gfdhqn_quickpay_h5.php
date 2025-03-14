<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gfdhqn.php';

/** 
 *
 * GFDHQN 艾比德
 * 
 * 
 * * 'GFDHQN_QUICKPAY_H5_PAYMENT_API', ID 
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gfdhqn_quickpay_h5 extends Abstract_payment_api_gfdhqn {

	public function getPlatformCode() {
		return GFDHQN_QUICKPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gfdhqn_quickpay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
           	$params['paytype'] = self::PAYTYPE_QUICKPAY_H5;
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
