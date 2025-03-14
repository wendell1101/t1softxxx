<?php
require_once dirname(__FILE__) . '/abstract_payment_api_25ypay.php';

/** 
 *
 * 25YPAY
 * 
 * 
 * * '_25YPAY_PAYMENT_API', ID 5401
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.25ypay.cn/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_25ypay extends Abstract_payment_api_25ypay {

    public function getPlatformCode() {
        return _25YPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return '25ypay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormPost($params);
    }

	// public function getPlayerInputInfo() {

    //     return array(
    //          array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
    //     );
    // }
}
