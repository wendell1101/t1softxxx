<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sctekpay.php';

/** 
 *
 * 盛灿
 * 
 * 
 * * SCTEKPAY_PAYMENT_API, ID: 463
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * LIVE-URL: https://upay.szyinfubao.com/quickPay/pay
 * * TEST-URL: http://routepay.snsshop.net/quickPay/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sctekpay_quickpay extends Abstract_payment_api_sctekpay {

	public function getPlatformCode() {
		return SCTEKPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'sctekpay_quickpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		// if (!empty($direct_pay_extra_info)) {
		// 	$extraInfo = json_decode($direct_pay_extra_info, true);
		// 	if (!empty($extraInfo)) {
		// 		$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
		// 	}
		// }

		unset($params['card_type']);
		unset($params['pay_type']);
		unset($params['body']);
		$params['user_ip'] = $this->CI->utils->getIP();
        // $params['user_ip'] = '114.32.45.138';
        $params['bind_status'] = '1';
	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}

	//Hide bank list dropdown
	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}
}
