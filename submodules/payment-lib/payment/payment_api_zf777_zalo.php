<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zf777.php';

/**
 *
 * ZF777
 *
 *
 * * 'ZF777_ZALO_PAYMENT_API', ID 5946
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
class Payment_api_zf777_zalo extends Abstract_payment_api_zf777 {

	public function getPlatformCode() {
		return ZF777_ZALO_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zf777_zalo';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['type'] = self::PAYWAY_ZALO;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormQRCode($params);
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
