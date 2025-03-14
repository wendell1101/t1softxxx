<?php
require_once dirname(__FILE__) . '/abstract_payment_api_flightpaying.php';

/** 
 *
 * FLIGHTPAYING  聚联支付
 * 
 * 
 * * 'FLIGHTPAYING_UNIONAPAY_PAYMENT_API', ID 869
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
class Payment_api_flightpaying_unionapay extends Abstract_payment_api_flightpaying {

	public function getPlatformCode() {
		return FLIGHTPAYING_UNIONAPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'flightpaying_unionapay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['selectFinaCode'] = self::SCANTYPE_UNIONAPAY;
            $params['tranAttr'] = self::TRANATTR_H5;
        }
        else {
            $params['selectFinaCode'] = self::SCANTYPE_UNIONAPAY;
            $params['tranAttr'] = self::TRANATTR_NATIVE;
        }
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
