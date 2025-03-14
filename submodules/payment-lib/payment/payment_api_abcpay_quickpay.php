<?php
require_once dirname(__FILE__) . '/abstract_payment_api_feifu8pay.php';

/** 
 *
 * ABCPAY
 * 
 * 
 * * 'ABCPAY_QUICKPAY_PAYMENT_API', ID 5424
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.abc555888.cc/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_abcpay_quickpay extends Abstract_payment_api_feifu8pay {

	public function getPlatformCode() {
		return ABCPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'abcpay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['pay_type'] = $this->getSystemInfo('pay_type','unionpay2');

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
