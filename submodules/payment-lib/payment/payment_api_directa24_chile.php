<?php
require_once dirname(__FILE__) . '/abstract_payment_api_directa24.php';

/**
 *
 * * DIRECTA24_CHILE_PAYMENT_API, ID: 6226
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_directa24_chile extends Abstract_payment_api_directa24 {

	public function getPlatformCode() {
		return DIRECTA24_CHILE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'directa24_chile';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$extraInfo = json_decode($direct_pay_extra_info, true);
        $params['country']        = $this->getSystemInfo('payment_country') ? $this->getSystemInfo('payment_country') : self::PAYMENT_COUNTRY_CL;
        $params['currency']       = isset($extraInfo['currency']) ? $extraInfo['currency'] : $this->getSystemInfo("USD", self::CURRENCY);
		$params['payment_method'] = $extraInfo['payment_method'];
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		$country = $this->getSystemInfo('payment_country') ? $this->getSystemInfo('payment_country') : self::PAYMENT_COUNTRY_CL;
		$this->CI->utils->debug_log('=====================directa24 country', $country);
		$params['country'] = $country;
		$this->CI->utils->debug_log('=====================directa24 params', $params);
		$payment_methotd = $this->getPaymentMethods($params);
		$this->CI->utils->debug_log('=====================directa24 payment_methotd', $payment_methotd);
		$payment_methotd = json_decode($payment_methotd, true);
		$this->CI->utils->debug_log('=====================directa24 decode payment_methotd', $payment_methotd);

		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
			array('name' => 'payment_method', 'type' => 'list_custom', 'label_lang' => 'cashier.payment_method' ,'list' => $payment_methotd)
		);
	}

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
	}
}
