<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpaycrypto.php';

/**
 * CPAYCRYPTO
 *
 * * CPAYCRYPTO_USDT_PAYMENT_API, ID: 6223
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * 
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * 
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cpaycrypto_usdt extends Abstract_payment_api_cpaycrypto {

	public function getPlatformCode() {
		return CPAYCRYPTO_USDT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cpaycrypto_usdt';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['amount']  = $extraInfo['crypto_amount'];
            }
        }
	}

	# Hide bank list dropdown
    public function getPlayerInputInfo() {

		return array(
			array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang('BRL-Yuan')),
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
