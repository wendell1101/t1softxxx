<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hogzbo.php';

/** 
 *
 * HOGZBO
 * 
 * 
 * * HOGZBO_QQPAY_PAYMENT_API, ID: 636
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.hogzbo.com/payment/PayApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hogzbo_qqpay_h5 extends Abstract_payment_api_hogzbo {

	public function getPlatformCode() {
		return HOGZBO_QQPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hogzbo_qqpay_h5';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['payType'] = self::PAYTYPE_QQPAY_WAP;
        $params['ip'] = $this->getClientIp();
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
