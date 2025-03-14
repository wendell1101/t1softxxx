<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dlkepay.php';

/** 
 *
 * dlkepay 联科支付 支付寶 / 畅付(支付寶)
 * 
 * 
 * * DLKEPAY_ALIPAY_H5_PAYMENT_API, ID: 5058
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.qqqtba.cn/ChargeBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dlkepay_alipay_h5 extends Abstract_payment_api_dlkepay {

	public function getPlatformCode() {
		return DLKEPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dlkepay_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['type'] = self::SCANTYPE_ALIPAY_H5;
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
