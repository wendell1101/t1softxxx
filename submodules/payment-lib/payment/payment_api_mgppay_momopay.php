<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mgppay.php';

/**
 * mgppay
 *
 * * MGPPAY_MOMO_PAYMENT_API', ID 6170
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.hongzhong777.com/gateway/pay.jsp
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mgppay_momopay extends Abstract_payment_api_mgppay {

	public function getPlatformCode() {
		return MGPPAY_MOMOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mgppay_momopay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channleType'] = self::CHANNEL_TYPE_MOMO;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormURL($params);
	}

	  # Hide bank selection drop-down
	  public function getPlayerInputInfo()
	  {
		   return array(array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'));
	  }
}
