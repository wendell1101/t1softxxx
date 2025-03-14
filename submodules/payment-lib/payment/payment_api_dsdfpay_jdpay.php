<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dsdfpay.php';

/**
 * DSDFPAY 代收代付
 * https://www.dsdfpay.com/html/admin/login.html
 *
 * DSDFPAY_JDPAY_PAYMENT_API, ID: 778
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.dsdfpay.com/dsdf/customer_pay/init_din
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dsdfpay_jdpay extends Abstract_payment_api_dsdfpay {

	public function getPlatformCode() {
		return DSDFPAY_JDPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dsdfpay_jdpay';
	}

	public function getBankType($direct_pay_extra_info) {
		return "qrcode";

	}

	public function getTypeFlag($direct_pay_extra_info) {
		return "JDPAY";
	}

	/* hide bar */
	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}

}