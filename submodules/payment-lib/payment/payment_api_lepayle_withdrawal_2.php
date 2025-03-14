<?php
require_once dirname(__FILE__) . '/payment_api_lepayle_withdrawal.php';

/**
 * 新乐付 LEPAYLE
 * https://cms.lepayle.com/
 *
 * * LEPAYLE_WITHDRAWAL_2_PAYMENT_API, ID: 286
 *
 * Required Fields:
 * 
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * 
 * * URL: https://service.lepayle.com/api/quickdraw
 * * Account: ## partner ID ##
 * * Extra Info:
 * > {
 * > 	"lepayle_priv_key" : "## merchant private key (pem formatted, escaped, no start/end tag) ##",
 * > 	"lepayle_pub_key" : "## API public key (pem formatted, escaped, no start/end tag) ##",
 * > 	"callback_host" : ""
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_lepayle_withdrawal_2 extends Payment_api_lepayle_withdrawal {

	public function getPlatformCode() {
		return LEPAYLE_WITHDRAWAL_2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'lepayle_withdrawal_2';
	}
}
