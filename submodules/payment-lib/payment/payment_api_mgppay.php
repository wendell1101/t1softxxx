<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mgppay.php';

/**
 * MGPPAY
 *
 * * MGPPAY_PAYMENT_API', ID 6169
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
class Payment_api_mgppay extends Abstract_payment_api_mgppay {

	public function getPlatformCode() {
		return MGPPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'mgppay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channleType'] = self::CHANNEL_TYPE_ONLINE;
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
