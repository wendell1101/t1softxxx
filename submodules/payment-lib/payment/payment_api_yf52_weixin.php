<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yf52.php';

/**
 *
 * * YF52_WEIXIN_PAYMENT_API, ID: 481
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://way.yf52.com/api/pay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yf52_weixin extends Abstract_payment_api_yf52 {

	public function getPlatformCode() {
		return YF52_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yf52_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->CI->utils->is_mobile()) {
			if($this->getSystemInfo('use_weixin_pc_gateway') == true) {
				$params['p_type'] = self::PAYTYPE_WEIXIN;
			}
			else {
				$params['p_type'] = self::PAYTYPE_WEIXIN_WAP;
			}			
		}
		else {
			$params['p_type'] = self::PAYTYPE_WEIXIN;
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
