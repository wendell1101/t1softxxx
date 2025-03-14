<?php
require_once dirname(__FILE__) . '/abstract_payment_api_youpay.php';

/**
 * YOUPAY 友付 - 支付寶扫码
 *
 *
 * YOUPAY_ALIPAY_PAYMENT_API, ID: 5296
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.surperpay.com/pay/nativePay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_youpay_alipay extends Abstract_payment_api_youpay {

	public function getPlatformCode() {
		return YOUPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'youpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
            $params['payType'] = self::PAYTYPE_ALIPAY_WAP;
        }
        else {
            $params['payType'] = self::PAYTYPE_ALIPAY;
        }
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlRedirect($params);
	}

}
