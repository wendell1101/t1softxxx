<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zsagepay.php';

/**
 * ZSAGEPAY 泽圣
 * http://payment.zsagepay.com/
 *
 * * ZSAGEPAY_PAYMENT_API, ID: 309
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 * * Key - Signing key
 * * Extra Info
 *
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zsagepay_weixin extends Abstract_payment_api_zsagepay {

	public function getPlatformCode() {
		return ZSAGEPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zsagepay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['model'] = 'QR_CODE';
		$params['isSupportCredit'] = '1';   //是否支持信用卡
		$params['ip'] = $this->getClientIp();
		$params['payChannel'] = '21';	//21 微信，30-支付宝，31-QQ 钱包
		unset($params['totalAmount']); //totalAmount
		unset($params['bankCardType']); 
		
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormQRCode($params);
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}