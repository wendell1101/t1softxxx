<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xqiangpay.php';
/**
 *
 * * XQIANGPAY_UNIONPAY_PAYMENT_API, ID: 671
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.xqiangpay.net/website/pay.htm 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2017-2022 tot
 */
class Payment_api_xqiangpay_unionpay extends Abstract_payment_api_xqiangpay {

	public function getPlatformCode() {
		return XQIANGPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xqiangpay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
			$params['payType'] = self::PAYTYPE_UNIONPAY;
			$params['orgCode'] = self::ORGCODE_UNIONPAY;
            $params['pureQr'] = self::RETURN_QRCODE_MODE;
			
	}	

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}
}
