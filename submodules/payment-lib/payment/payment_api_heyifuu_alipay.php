<?php
require_once dirname(__FILE__) . '/abstract_payment_api_heyifuu.php';
/**
 * HEYIFUU 合意付
 * http://merchant.heyifuu.cn/
 *
 * HEYIFUU_ALIPAY_PAYMENT_API, ID: 359
 *
 * General behavior includes :
 *
 * * Recieving callbacks
 * * Generate payment forms
 * * Checking of callback orders
 * * Get bank details
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * Live URL: http://pay.heyifuu.cn/cgi-bin/netpayment/pay_gate.cgi
 * * Extra Info
 * > {
 * >     "heyifuu_apiVersion": "1.0.0.0",
 * >     "heyifuu_platformID": "##platform ID##",
 * >     "heyifuu_merchNo": "##merchant ID##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_heyifuu_alipay extends Abstract_payment_api_heyifuu {
	const HEYIFUU_APINAME_PAY = "WEB_PAY_B2C";
	const HEYIFUU_CALLBACK = "PAY_RESULT_NOTIFY";
	const RETURN_SUCCESS_CODE = 'SUCCESS';
	const RETURN_FAILED_CODE = 'FAILED';
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	public function getPlatformCode() {
		return HEYIFUU_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'heyifuu_alipay';
	}

	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['choosePayType'] = '4';
	}
}