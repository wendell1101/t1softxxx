<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gm.php';

/**
 * GMStone
 * http://www.gmstoneft.com
 *
 * * GM_ALIPAY_QRONLY_PAYMENT_API, ID: 225
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.master-egg.cn/GateWay/ReceiveBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gm_alipay_qronly extends Abstract_payment_api_gm {

	public function getPlatformCode() {
		return GM_ALIPAY_QRONLY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gm_alipay_qronly';
	}

	public function getBankType($direct_pay_extra_info) {
		return parent::BANK_TYPE_ALIPAY;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {

		// if($this->CI->utils->is_mobile()){

		// 	return array(
		// 		'success' => true,
		// 		'type' => self::REDIRECT_TYPE_FORM,
		// 		'url' => $this->getSystemInfo('url'),
		// 		'params' => $params,
		// 		'use_iframe' => false,
		// 		'post' => true,
		// 	);

		// }else{

		//only qrcode

			$result = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['p2_Order']);

			$this->CI->utils->debug_log('result', $result, $this->getSystemInfo('url'), $params);

			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'base64' => base64_encode($result),
			);
		// }
	}

}
