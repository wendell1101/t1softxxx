<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hbepay.php';

/** 
 *
 * HBEPAY 汇宝
 * 
 * 
 * * 'HBEPAY_QUICKPAY_PAYMENT_API', ID 5307
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
class Payment_api_hbepay_quickpay extends Abstract_payment_api_hbepay {

	public function getPlatformCode() {
		return HBEPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hbepay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['service'] = 'S1007';
		$params['sign_type'] = 'MD5';
        $params['subject'] = 'Deposit';
		$params['sub_body'] = 'deposit';
        $params['sign'] = $this->MD5sign($params);
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
