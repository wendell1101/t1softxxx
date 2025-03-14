<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xfuoo.php';

/**
 *
 * * XFUOO_QQPAY_PAYMENT_API, ID: 383
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://ebank.xfuoo.com
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xfuoo_qqpay extends Abstract_payment_api_xfuoo {

	public function getPlatformCode() {
		return XFUOO_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xfuoo_qqpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			$params['defaultbank'] = self::DEFAULTBANK_QQPAY;
			$params['isApp']       = 'H5';
			$params['userIp']      = $this->getClientIp();
			$params['appName']     = 'Deposit';
			$params['appMsg']      = 'Deposit';
			$params['appType']     = 'wap';
			$params['backUrl']     = $params['returnUrl'];
		}else {
			$params['defaultbank'] = self::DEFAULTBANK_QQPAY;
			$params['isApp']       = 'web';
		}
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
