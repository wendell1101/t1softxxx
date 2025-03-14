<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hftpay.php';

/** 
 *
 * hftpay
 * 
 * 
 * * 'HFTPAY_ALIPAY_PAYMENT_API', ID 5240
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.107.242.216/pay/api/api.php?action=pay&m=pay_it
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hftpay_alipay extends Abstract_payment_api_hftpay {

	public function getPlatformCode() {
		return HFTPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hftpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['code_type'] = self::CODE_TYPE_ALIPAY;
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
