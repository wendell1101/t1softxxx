<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yikuaipay.php';

/**
 * YIKUAIPAY  壹快付
 *
 * * YIKUAIPAY_QQPAY_PAYMENT_API, ID: 613
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.yikuaipay.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yikuaipay extends Abstract_payment_api_yikuaipay {

	public function getPlatformCode() {
		return YIKUAIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yikuaipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
                $params['bankcode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
        }

        if($this->CI->utils->is_mobile()) {
			$params['Product'] = self::PRODUCT_BANK_WAP;
		}
		else {
			$params['Product'] = self::PRODUCT_BANK_WEP;
		}
	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
