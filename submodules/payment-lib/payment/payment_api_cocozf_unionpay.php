<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cocozf.php';

/** 
 *
 * cocozf
 * 
 * 
 * * 'COCOZF_UNIONPAY_PAYMENT_API', ID 5242
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
class Payment_api_cocozf_unionpay extends Abstract_payment_api_cocozf {

	public function getPlatformCode() {
		return COCOZF_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cocozf_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
            $params['tranType'] = self::SCANTYPE_UNIONPAY;
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
