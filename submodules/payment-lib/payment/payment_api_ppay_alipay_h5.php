<?php
require_once dirname(__FILE__) . '/payment_api_ppay_alipay.php';
/**
 * PPAY PPAY支付
 * 
 *
 * PPAY_ALIPAY_H5_PAYMENT_API, ID: 5470
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://45.249.247.175/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_ppay_alipay_h5 extends Payment_api_ppay_alipay {

	public function getPlatformCode() {
		return PPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ppay_alipay_h5';
    }

}
