<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tpay.php';
/**
 * TPAY
 *
 * * TPAY_PAYMENT_API, ID: 5724
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.tapexdd12.com/app/pay/pay.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tpay extends Abstract_payment_api_tpay {

	public function getPlatformCode() {
		return TPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'tpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['freePrice'] = self::ORDERTYPE_TYPE_ONLINEBANK;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}