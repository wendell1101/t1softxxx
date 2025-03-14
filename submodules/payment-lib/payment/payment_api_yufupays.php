<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yufupays.php';
/**
 * YUFUPAYS 优付
 *
 * * YUFUPAYS_PAYMENT_API, ID: 5687
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.yufupays.com/Pay/GateWay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yufupays extends Abstract_payment_api_yufupays {

	public function getPlatformCode() {
		return YUFUPAYS_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yufupays';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['type'] = self::ORDERTYPE_TYPE_ONLINEBANK;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}