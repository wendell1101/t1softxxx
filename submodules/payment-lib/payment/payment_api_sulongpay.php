<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sulongpay.php';
/**
 * SULONGPAY 
 *
 * * SULONGPAY_PAYMENT_API,  ID: 5274
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 *
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 * * Extra Info:
 * > { 
 * >    "gateway_url": "http://pay.sulongpay.com/gateway/payment"
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sulongpay extends Abstract_payment_api_sulongpay {

	public function getPlatformCode() {
		return SULONGPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sulongpay';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }

        $params['trade_type'] = self::PAYTYPE_BANK;
    }

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}


}
