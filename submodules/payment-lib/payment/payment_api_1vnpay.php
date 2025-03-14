<?php
require_once dirname(__FILE__) . '/abstract_payment_api_1vnpay.php';

/**
 *
 * * _1VNPAY_PAYMENT_API, ID: 6018
 * 
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.1vnpay.org/api/v1/fundtransfer
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_1vnpay extends Abstract_payment_api_1vnpay {

	public function getPlatformCode() {
		return _1VNPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return '1vnpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['bank_code'] = $bank;
		$params['channel'] = self::CHANNEL_BANK_QR;
	}	

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

	protected function getBankListInfoFallback() {
        return array(
            array('value' => 'VP', 'label' => 'Vp Bank'),
            array('value' => 'ACB', 'label' => 'Acb Bank'),
            array('value' => 'BIDV', 'label' => 'BIDV Bank'),
            array('value' => 'VTB', 'label' => 'Vietin Bank'),
            array('value' => 'MB', 'label' => 'MB Bank'),
            array('value' => 'EXB', 'label' => 'EXIM Bank'),
            array('value' => 'SAC', 'label' => 'Sacom Bank'),
            array('value' => 'TCB', 'label' => 'TECHCOM Bank'),
            array('value' => 'TPB', 'label' => 'TPB Bank'),
            array('value' => 'VCB', 'label' => 'Vietcom Bank'),
            array('value' => 'SCB', 'label' => 'SCB Bank'),
        );
    }
}
