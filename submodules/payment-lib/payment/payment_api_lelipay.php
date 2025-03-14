<?php
require_once dirname(__FILE__) . '/abstract_payment_api_lelipay.php';

/**
 * lelipay
 *
 * * LELIPAY_PAYMENT_API, ID: 6256
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_lelipay extends Abstract_payment_api_lelipay {

    public function getPlatformCode() {
        return LELIPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'lelipay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    public function getBankCode($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}