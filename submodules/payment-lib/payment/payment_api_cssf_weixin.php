<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cssf.php';

/**
 * CSSF 彩世商付 - 微信
 * 
 *
 * CSSF_WEIXIN_PAYMENT_API, ID: 342
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://120.27.218.52:8081/openapi/pay/scanqrcode/qrcodepay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cssf_weixin extends Abstract_payment_api_cssf {

	public function getPlatformCode() {
		return CSSF_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cssf_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		}

		$params['payType'] = self::PAYTYPE_WEIXIN;
		$params['payAmount'] = $params['transAmount'];
		$params['goodName'] = 'Deposit';
		$params['goodDesc'] = 'Deposit';
		$params['goodNum'] = '1';
		$params['busType'] = 'Deposit';
		$params['notifyUrl'] = $params['tradeNotifyUrl'];

		unset($params['transAmount']);
		unset($params['goodsName']);
		unset($params['goodsDesc']);
		unset($params['terminalType']);
		unset($params['pageNotifyUrl']);
		unset($params['tradeNotifyUrl']);	
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
