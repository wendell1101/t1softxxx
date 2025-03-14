<?php
require_once dirname(__FILE__) . '/abstract_payment_api_p2p.php';

/**
 *
 * p2p
 *
 * * 'P2P_PAYMENT_API', ID 6110
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_p2p extends Abstract_payment_api_p2p {

	public function getPlatformCode() {
		return P2P_PAYMENT_API;
	}

	public function getPrefix() {
		return 'p2p';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['paytype'] = self::PAY_CODE_P2P;
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
