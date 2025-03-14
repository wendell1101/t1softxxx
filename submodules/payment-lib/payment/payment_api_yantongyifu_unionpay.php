<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yantongyifu.php';

/** 
 *
 * yantongyifu 易付
 * 
 * 
 * * 'YANTONGYIFU_UNIONPAY_PAYMENT_API', ID 5162
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http:// 212.64.89.203:8889/tran/cashier/pay.ac
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yantongyifu_unionpay extends Abstract_payment_api_yantongyifu {

	public function getPlatformCode() {
		return YANTONGYIFU_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yantongyifu_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['tranType'] = self::TRANTYPE_UNIONAPAY;
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
