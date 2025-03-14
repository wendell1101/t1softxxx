<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gpay.php';

/**
 * GPAY
 *
 * * GPAY_ALIPAY_H5_PAYMENT_API, ID: 5514
 * 
 * *
 * Required Fields:
 * * Account
 * * Key
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Live Key ## 
 * * URL: https://cfpay.xyz/inject/newOrderPayByAccesser
 * * Extra Info:
 * > {
 * >	    "gpay_auth_token": "",
 * >	    "gpay_iid": "",
 * >	    "gpay_app_id": "",
 * >	    "gpay_app_token":""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gpay_alipay_h5 extends Abstract_payment_api_gpay {

	public function getPlatformCode() {
		return GPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_ALIPAY_H5;
	}

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
