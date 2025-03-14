<?php
require_once dirname(__FILE__) . '/abstract_payment_api_aobangpay.php';
/**
 * aobangpay  奥邦
 *
 * * AOBANGPAY_ALIPAY_PAYMENT_API, ID: 5049
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.aobang2pay.com/pay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_aobangpay_alipay extends Abstract_payment_api_aobangpay {

	public function getPlatformCode() {
		return AOBANGPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'aobangpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->CI->utils->is_mobile()) {
            $params['product_type'] = self::PRODUCT_TYPE_ALIPAY_H5;
        }
        else {
            $params['product_type'] = self::PRODUCT_TYPE_ALIPAY;
        }
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
