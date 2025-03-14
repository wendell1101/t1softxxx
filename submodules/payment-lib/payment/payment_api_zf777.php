<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zf777.php';

/**
 *
 * ZF777
 *
 *
 * * 'ZF777_PAYMENT_API', ID 5944
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.zf77777.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zf777 extends Abstract_payment_api_zf777 {

	public function getPlatformCode() {
		return ZF777_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zf777';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['type'] = $bank;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }

}
