<?php
require_once dirname(__FILE__) . '/payment_api_lepayle_withdrawal.php';

/**
 * 新乐付 LEPAYLE
 * https://cms.lepayle.com/
 *
 * * LEPAYLE_WITHDRAWAL_3_PAYMENT_API, ID: 287
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
class Payment_api_lepayle_withdrawal_3 extends Payment_api_lepayle_withdrawal {

	public function getPlatformCode() {
		return LEPAYLE_WITHDRAWAL_3_PAYMENT_API;
	}

	public function getPrefix() {
		return 'LEPAYLE_WITHDRAWAL_3_PAYMENT_API';
	}
}
