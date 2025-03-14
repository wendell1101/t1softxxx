<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yonkopay.php';

/**
 *
 * YONKOPAY
 *
 *
 * * 'YONKOPAY_PAYMENT_API', ID 5980
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.YONKOPAY77.org/api/create
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yonkopay extends Abstract_payment_api_yonkopay {

	public function getPlatformCode() {
		return YONKOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yonkopay';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params["channel"] = self::PAYWAY_ONLINEBANK;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
