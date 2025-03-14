<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pppay.php';

/** 
 *
 * PPPAY QUICKPAY 快捷
 * *
 * * 
 * * 'PPPAY_QUICKPAY_PAYMENT_API', ID 5137

 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.63405.com/mctrpc/order/mkReceiptOrder.htm
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pppay_quickpay extends Abstract_payment_api_pppay {

	public function getPlatformCode() {
		return PPPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'pppay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['payment_1'] = self::PAYTYPE_QUICKPAY.$params['requestAmount'];
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
