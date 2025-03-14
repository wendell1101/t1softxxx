<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pkriver.php';

/** 
 *
 *   pkriver 鼎盛 支付寶 h5
 * 
 * 
 * * 'PKRIVER_ALIPAY_H5_PAYMENT_API', ID 936
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pkriver.com/api/v3/cashier.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pkriver_alipay_h5 extends Abstract_payment_api_pkriver {

	public function getPlatformCode() {
		return PKRIVER_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pkriver_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['qrtype'] = $this->getSystemInfo('qrtype',self::SCANTYPE_ALIPAY_H5);
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
