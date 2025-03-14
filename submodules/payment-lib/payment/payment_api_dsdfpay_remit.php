<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dsdfpay.php';

/**
 * DSDFPAY 代收代付
 * https://www.dsdfpay.com/html/admin/login.html
 *
 * DSDFPAY_REMIT_PAYMENT_API, ID: 333
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
class Payment_api_dsdfpay_remit extends Abstract_payment_api_dsdfpay {

	public function getPlatformCode() {
		return DSDFPAY_REMIT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dsdfpay_remit';
	}

	public function getBankType($direct_pay_extra_info) {
		return "remit";
	}

	public function getTypeFlag($direct_pay_extra_info) {
		return "";
	}

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
