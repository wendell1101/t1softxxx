<?php
require_once dirname(__FILE__) . '/abstract_payment_api_largepay.php';

/**
 * LARGEPAY
 *
 * * LARGEPAY_PAYMENT_API, ID: 5532
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
class Payment_api_largepay extends Abstract_payment_api_largepay {

	public function getPlatformCode() {
		return LARGEPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'largepay';
	}

	public function getBankType($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		} else {
			return parent::getBankType($direct_pay_extra_info);
		}
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_mode'] = self::PAYMODE_WEB;
		$params['bank_code'] = $this->getBankType($direct_pay_extra_info);
		$params['card_type'] = self::CARD_TYPE;
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
