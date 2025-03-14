<?php
require_once dirname(__FILE__) . '/abstract_payment_api_flightpaying.php';

/** 
 *
 * FLIGHTPAYING  聚联支付 支付寶H5
 * 
 * 
 * * 'FLIGHTPAYING_ALIPAY_H5_PAYMENT_API', ID 877
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.feifu8.com/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_flightpaying_alipay_h5 extends Abstract_payment_api_flightpaying {

	public function getPlatformCode() {
		return FLIGHTPAYING_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'flightpaying_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
			$params['service'] = 'fuseJumpProxy';
            $params['selectFinaCode'] = self::SCANTYPE_ALIPAY;
            $params['tranAttr'] = self::TRANATTR_H5;
     
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
