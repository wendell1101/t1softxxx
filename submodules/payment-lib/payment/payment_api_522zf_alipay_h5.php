<?php
require_once dirname(__FILE__) . '/payment_api_522zf_alipay.php';

/**
 * 522ZF
 *
 * * _522ZF_ALIPAY_H5_PAYMENT_API, ID: 832
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://13.230.254.56:11010/admin/to_login
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_522zf_alipay_h5 extends Payment_api_522zf_alipay {

	public function getPlatformCode() {
		return _522ZF_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return '522zf_alipay_h5';
	}

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
	}

}