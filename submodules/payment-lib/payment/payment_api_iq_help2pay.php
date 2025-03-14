<?php
require_once dirname(__FILE__) . '/abstract_payment_api_iq.php';

/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_HELP2PAY_PAYMENT_API, ID: 5564
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq/api/bank/deposit/process
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_iq_help2pay extends Abstract_payment_api_iq {

    public function getPlatformCode() {
        return IQ_HELP2PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'iq_help2pay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
		$params['service'] = $this->getBankType($direct_pay_extra_info);
    }

    protected function processPaymentUrlForm($params,$orderId) {
        return $this->processPaymentUrlFormForRedirect($params, $orderId);
    }

	public function getBankType($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		} else {
			return parent::getBankType($direct_pay_extra_info);
		}
	}

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'Maybank Berhad', 'value' => 'MBB'),
            array('label' => 'Public Bank Berhad', 'value' => 'PBB'),
            array('label' => 'CIMB Bank Berhad', 'value' => 'CIMB'),
            array('label' => 'Hong Leong Bank Berhad', 'value' => 'HLB'),
            array('label' => 'RHB Banking Group', 'value' => 'RHB'),
            array('label' => 'AmBank Group', 'value' => 'AMB'),
            array('label' => 'Bank Islam Malaysia', 'value' => 'BIMB'),
            array('label' => 'Karsikorn Bank (K-Bank)', 'value' => 'KKR'),
            array('label' => 'Bangkok Bank', 'value' => 'BBL'),
            array('label' => 'Siam Commercial Bank', 'value' => 'SCB'),
            array('label' => 'Krung Thai Bank', 'value' => 'KTB'),
            array('label' => 'Bank of Ayudhya (Krungsri)', 'value' => 'BOA'),
            array('label' => 'Government Savings Bank', 'value' => 'GSB'),
            array('label' => 'TMB Bank Public Company Limited', 'value' => 'TMB'),
            array('label' => 'CIMB Thai', 'value' => 'CIMBT'),
            array('label' => 'Kiatnakin Bank', 'value' => 'KNK'),
            array('label' => 'Techcombank', 'value' => 'TCB'),
            array('label' => 'Sacombank', 'value' => 'SACOM'),
            array('label' => 'Vietcombank', 'value' => 'VCB'),
            array('label' => 'Asia Commercial Bank', 'value' => 'ACB'),
            array('label' => 'DongA Bank', 'value' => 'DAB'),
            array('label' => 'Vietinbank', 'value' => 'VTB'),
            array('label' => 'Bank for Investment and Development of Vietnam', 'value' => 'BIDV'),
            array('label' => 'Eximbank Vietnam', 'value' => 'EXIM'),
            array('label' => 'Bank Central Asia', 'value' => 'BCA'),
            array('label' => 'Bank Negara Indonesia', 'value' => 'BNI'),
            array('label' => 'Bank Rakyat Indonesia', 'value' => 'BRI'),
            array('label' => 'Mandiri Bank', 'value' => 'MDR'),
            array('label' => 'CIMB Niaga', 'value' => 'CIMBN'),
            array('label' => 'Banco de Oro', 'value' => 'BDO'),
            array('label' => 'MetroBank', 'value' => 'MTB'),
        );
    }
}
