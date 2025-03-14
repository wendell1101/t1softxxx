<?php
require_once dirname(__FILE__) . '/abstract_payment_api_eeziepay.php';

/**
 *
 * * EEZIEPAY_PAYMENT_API, ID 5398
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.gzshop318.com/fundtransfer.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 * > {
 * >    "sellerEmail" : "## Seller email address, system will show you when the merchant opens ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_eeziepay extends Abstract_payment_api_eeziepay {

	public function getPlatformCode() {
		return EEZIEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'eeziepay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
	}

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    

	public function getBankListInfoFallback() {
		return array(
            array('label' => 'Bank Rakyat Indonesia', 'value' => 'BRI'),
            array('label' => 'Bank Negara Indonesia', 'value' => 'BNI'),
            array('label' => 'Bank Central Asia', 'value' => 'BCA'),
            array('label' => 'Bank Mandiri', 'value' => 'MDR')
		);
	}
}
