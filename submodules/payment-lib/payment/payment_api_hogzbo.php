<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hogzbo.php';

/** 
 *
 * HOGZBO
 * 
 * 
 * * HOGZBO_PAYMENT_API, ID: 738
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://bofubao.qingzhuzi.com/qingpay.php
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hogzbo extends Abstract_payment_api_hogzbo {

	public function getPlatformCode() {
		return HOGZBO_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hogzbo';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}
		
		$params['channel'] = '2';
		$params['bankCode'] = $bank;
		$params['settleType'] = '2';


	}


	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}
}
