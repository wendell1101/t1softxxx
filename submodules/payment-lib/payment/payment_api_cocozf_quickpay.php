<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cocozf.php';

/** 
 *
 * cocozf
 * 
 * 
 * * 'COCOZF_QUICKPAY_PAYMENT_API', ID 5199
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.mqqpay.com/ctp_xa/view/server/aotori/queryTrans.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cocozf_quickpay extends Abstract_payment_api_cocozf {

	public function getPlatformCode() {
		return COCOZF_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cocozf_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
           	$params['tranType'] = self::PAYTYPE_QUICK_PAY;
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
